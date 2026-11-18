<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Listener;

use Flarum\Approval\Event\PostWasApproved;
use Flarum\Extension\ExtensionManager;
use Flarum\Mentions\Job\SendMentionsNotificationsJob;
use Flarum\Mentions\UserMentionCount;
use Flarum\Post\CommentPost;
use Flarum\Post\Event\Posted;
use Flarum\Post\Event\Restored;
use Flarum\Post\Event\Revised;
use Flarum\Post\Post;
use Illuminate\Contracts\Queue\Queue;
use s9e\TextFormatter\Utils;

class UpdateMentionsMetadataWhenVisible
{
    public function __construct(
        protected ExtensionManager $extensions,
        protected Queue $queue,
        protected UserMentionCount $mentionCount
    ) {
    }

    public function handle(Restored|Revised|Posted|PostWasApproved $event): void
    {
        if (! $event->post instanceof CommentPost) {
            return;
        }

        $content = $event->post->parsed_content;

        if ($content === null) {
            return;
        }

        $changes = $this->syncUserMentions(
            $event->post,
            $userMentions = Utils::getAttributeValues($content, 'USERMENTION', 'id')
        );

        // An approved post was already synced while it was private, so its
        // mentions start counting now even though none of them changed.
        $affected = array_merge(
            $changes['attached'],
            $changes['detached'],
            ($event instanceof PostWasApproved || $event instanceof Restored) ? $userMentions : []
        );
        $this->mentionCount->refresh($affected);

        // A new discussion caches the request actor as its author. The post
        // can have a separate author instance, so keep both models current.
        $actor = $event instanceof Posted ? $event->actor : null;
        $author = $event->post->relationLoaded('user') ? $event->post->user : null;
        $counts = [];

        foreach ([$actor, $author] as $user) {
            if ($user && in_array($user->id, $affected)) {
                $counts[$user->id] ??= $user->newQuery()->whereKey($user->id)->value('mention_count');
                $user->mention_count = $counts[$user->id];
                $user->syncOriginalAttribute('mention_count');
            }
        }

        $this->syncPostMentions(
            $event->post,
            $postMentions = Utils::getAttributeValues($content, 'POSTMENTION', 'id')
        );

        $this->syncGroupMentions(
            $event->post,
            $groupMentions = Utils::getAttributeValues($content, 'GROUPMENTION', 'id')
        );

        if ($this->extensions->isEnabled('flarum-tags')) {
            $this->syncTagMentions(
                $event->post,
                Utils::getAttributeValues($content, 'TAGMENTION', 'id')
            );
        }

        $this->queue->push(new SendMentionsNotificationsJob($event->post, $userMentions, $postMentions, $groupMentions));
    }

    protected function syncUserMentions(Post $post, array $mentioned): array
    {
        $changes = $post->mentionsUsers()->sync($mentioned);
        $post->unsetRelation('mentionsUsers');

        return $changes;
    }

    protected function syncPostMentions(Post $reply, array $mentioned): void
    {
        $reply->mentionsPosts()->sync($mentioned);
        $reply->unsetRelation('mentionsPosts');
    }

    protected function syncGroupMentions(Post $post, array $mentioned): void
    {
        $post->mentionsGroups()->sync($mentioned);
        $post->unsetRelation('mentionsGroups');
    }

    protected function syncTagMentions(Post $post, array $mentioned): void
    {
        $post->mentionsTags()->sync($mentioned);
        $post->unsetRelation('mentionsTags');
    }
}
