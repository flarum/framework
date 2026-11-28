<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Listener;

use Flarum\Discussion\Discussion;
use Flarum\Discussion\Event\Deleted as DiscussionDeleted;
use Flarum\Gdpr\Events\Erased;
use Flarum\Gdpr\Events\Erasing;
use Flarum\Gdpr\Models\ErasureRequest;
use Flarum\Mentions\UserMentionCount;
use Flarum\Post\Event\Deleted as PostDeleted;
use Flarum\Post\Event\Deleting;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;

/**
 * Recounts users.mention_count after posts are deleted.
 *
 * Deleting a post through the API removes that post's rows one at a time, so
 * its Deleted event can recount them. A discussion's posts and a user's posts
 * are deleted in one query each, which raises no event per post, so the users
 * those posts mentioned are noted just before the rows go, and their counts
 * are written once the rows are gone.
 */
class UpdateMentionCountWhenPostsDeleted
{
    /**
     * The users each deletion removed mentions of, by deletion, until it has
     * finished.
     *
     * @var array<string, list<int>>
     */
    private array $pending = [];

    public function __construct(
        protected UserMentionCount $mentionCount,
        protected ConnectionInterface $db
    ) {
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(Deleting::class, $this->whenPostIsDeleting(...));
        $events->listen(PostDeleted::class, $this->whenPostIsDeleted(...));
        $events->listen('eloquent.deleting: '.Discussion::class, $this->whenDiscussionIsDeleting(...));
        $events->listen(DiscussionDeleted::class, $this->whenDiscussionIsDeleted(...));

        // Erasing a user's data is part of flarum/gdpr, which may not be
        // installed.
        if (class_exists(Erasing::class)) {
            $events->listen(Erasing::class, $this->whenUserIsErasing(...));
            $events->listen(Erased::class, $this->whenUserIsErased(...));
        }
    }

    public function whenPostIsDeleting(Deleting $event): void
    {
        $this->pending['post-'.$event->post->id] = $this->mentionedUserIds(
            $this->db->table('posts')->select('id')->where('id', $event->post->id)
        );
    }

    public function whenPostIsDeleted(PostDeleted $event): void
    {
        $this->recount('post-'.$event->post->id);
    }

    /**
     * Deleting a discussion deletes its posts in one query, so note who they
     * mention while they can still be read.
     */
    public function whenDiscussionIsDeleting(Discussion $discussion): void
    {
        $this->pending['discussion-'.$discussion->id] = $this->mentionedUserIds(
            $this->db->table('posts')->select('id')->where('discussion_id', $discussion->id)
        );
    }

    public function whenDiscussionIsDeleted(DiscussionDeleted $event): void
    {
        $this->recount('discussion-'.$event->discussion->id);
    }

    /**
     * Erasing a user's account deletes their posts in one query too. The
     * request is passed here before anything is removed.
     */
    public function whenUserIsErasing(Erasing $event): void
    {
        $request = $event->user;

        if ($request->processed_mode !== ErasureRequest::MODE_DELETION) {
            return;
        }

        $userId = (int) $request->user_id;

        $this->pending['user-'.$userId] = $this->mentionedUserIds(
            $this->db->table('posts')->select('id')->where('user_id', $userId)
        );
    }

    public function whenUserIsErased(Erased $event): void
    {
        $this->recount('user-'.$event->user->id);
    }

    /**
     * The users mentioned by the posts a subquery selects, read while those
     * posts still exist.
     *
     * @return list<int>
     */
    protected function mentionedUserIds(Builder $postIds): array
    {
        return $this->db->table('post_mentions_user')
            ->whereIn('post_id', $postIds)
            ->distinct()
            ->pluck('mentions_user_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    protected function recount(string $key): void
    {
        $userIds = $this->pending[$key] ?? [];

        unset($this->pending[$key]);

        $this->mentionCount->refresh($userIds);
    }
}
