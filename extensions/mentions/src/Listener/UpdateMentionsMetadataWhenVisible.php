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
        protected Queue $queue
    ) {
    }

    public function handle(Restored|Revised|Posted|PostWasApproved $event): void
    {
        if (! $event->post instanceof CommentPost) {
            return;
        }

        $content = $event->post->parsed_content;

        $this->syncUserMentions(
            $event->post,
            $userMentions = Utils::getAttributeValues($content, 'USERMENTION', 'id')
        );

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

    protected function syncUserMentions(Post $post, array $mentioned): void
    {
        $post->mentionsUsers()->sync($mentioned);
        $post->unsetRelation('mentionsUsers');
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
