<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Push;

use Flarum\Discussion\Discussion;
use Flarum\Discussion\Event\Deleted as DiscussionDeleted;
use Flarum\Discussion\Event\Hidden as DiscussionHidden;
use Flarum\Discussion\Event\Restored as DiscussionRestored;
use Flarum\Discussion\Event\Started;
use Flarum\Post\Event\Deleted as PostDeleted;
use Flarum\Post\Event\Hidden as PostHidden;
use Flarum\Post\Event\Posted;
use Flarum\Post\Event\Restored as PostRestored;
use Flarum\Realtime\Push\Jobs\SendRemovalJob;
use Flarum\Realtime\Push\Jobs\SendTriggerJob;
use Illuminate\Contracts\Events\Dispatcher;
use WeakMap;

/**
 * Hiding, restoring, deleting and approving, live.
 *
 * Members who can still see the item get its new state (moderators see a post
 * greyed out). Members who could see it and can't any more are told it's gone
 * (SendRemovalJob). Approved content is, for everyone who couldn't see it
 * before, new, so it's sent as a new post or discussion.
 *
 * Who could see a discussion has to be known before it's hidden or deleted:
 * afterwards the database no longer says. That's captured here as the model
 * is saved or deleted, held against the model object rather than on it (a
 * property would be saved as a column).
 */
class VisibilitySubscriber extends Subscriber
{
    public const POST_HIDDEN = 'postHidden';
    public const POST_RESTORED = 'postRestored';
    public const DISCUSSION_HIDDEN = 'discussionHidden';
    public const DISCUSSION_RESTORED = 'discussionRestored';

    /** @var WeakMap<Discussion, array{public: bool, users: int[]|null}|null> */
    protected WeakMap $snapshots;

    public function __construct(
        protected Audience $audience
    ) {
        $this->snapshots = new WeakMap();
    }

    public function subscribe(Dispatcher $events): void
    {
        $this->listen('eloquent.saving: '.Discussion::class, [$this, 'discussionSaving']);
        $this->listen('eloquent.deleting: '.Discussion::class, [$this, 'discussionDeleting']);

        $this->listen(DiscussionHidden::class, [$this, 'discussionHidden']);
        $this->listen(DiscussionRestored::class, [$this, 'discussionRestored']);
        $this->listen(DiscussionDeleted::class, [$this, 'discussionDeleted']);

        $this->listen(PostHidden::class, [$this, 'postHidden']);
        $this->listen(PostRestored::class, [$this, 'postRestored']);
        $this->listen(PostDeleted::class, [$this, 'postDeleted']);

        // flarum/approval, when installed.
        $this->listen('Flarum\\Approval\\Event\\PostWasApproved', [$this, 'postApproved']);
    }

    public function discussionSaving(Discussion $discussion): void
    {
        if ($discussion->exists && $discussion->isDirty('hidden_at') && $discussion->hidden_at !== null) {
            $this->snapshots[$discussion] = $this->audience->snapshot($discussion);
        }
    }

    public function discussionDeleting(Discussion $discussion): void
    {
        $this->snapshots[$discussion] = $this->audience->snapshot($discussion);
    }

    public function discussionHidden(DiscussionHidden $event): void
    {
        $this->queue()->push(SendRemovalJob::forDiscussion($event->discussion->id, $this->takeSnapshot($event->discussion)));
        $this->queue()->push(new SendTriggerJob(self::DISCUSSION_HIDDEN, $event->discussion, $event->actor));
    }

    public function discussionRestored(DiscussionRestored $event): void
    {
        $this->queue()->push(new SendTriggerJob(self::DISCUSSION_RESTORED, $event->discussion, $event->actor));
    }

    public function discussionDeleted(DiscussionDeleted $event): void
    {
        $this->queue()->push(SendRemovalJob::forDiscussion($event->discussion->id, $this->takeSnapshot($event->discussion)));
    }

    public function postHidden(PostHidden $event): void
    {
        $this->queue()->push(SendRemovalJob::forPost($event->post->id, $event->post->discussion_id));
        $this->queue()->push(new SendTriggerJob(self::POST_HIDDEN, $event->post, $event->actor));
    }

    public function postRestored(PostRestored $event): void
    {
        $this->queue()->push(new SendTriggerJob(self::POST_RESTORED, $event->post, $event->actor));
    }

    public function postDeleted(PostDeleted $event): void
    {
        $this->queue()->push(SendRemovalJob::forPost($event->post->id, $event->post->discussion_id));
    }

    public function postApproved(object $event): void
    {
        $post = $event->post;

        // The first post's approval approves the discussion with it.
        if ($post->number === 1 && $post->discussion) {
            $this->queue()->push(new SendTriggerJob(Started::class, $post->discussion, $event->actor ?? null));
        } else {
            $this->queue()->push(new SendTriggerJob(Posted::class, $post, $event->actor ?? null));
        }
    }

    /**
     * @return array{public: bool, users: int[]|null}|null
     */
    protected function takeSnapshot(Discussion $discussion): ?array
    {
        $snapshot = $this->snapshots[$discussion] ?? null;
        unset($this->snapshots[$discussion]);

        return $snapshot;
    }
}
