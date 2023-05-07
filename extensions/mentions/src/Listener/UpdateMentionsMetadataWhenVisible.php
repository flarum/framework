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
    /**
     * @var ExtensionManager
     */
    protected $extensions;

    /**
     * @var Queue
     */
    protected $queue;

    public function __construct(ExtensionManager $extensions, Queue $queue)
    {
        $this->extensions = $extensions;
        $this->queue = $queue;
    }

    /**
     * @param Posted|Restored|Revised|PostWasApproved $event
     */
    public function handle($event)
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

    protected function syncUserMentions(Post $post, array $mentioned)
    {
        $post->mentionsUsers()->sync($mentioned);
        $post->unsetRelation('mentionsUsers');
    }

    protected function syncPostMentions(Post $reply, array $mentioned)
    {
        $reply->mentionsPosts()->sync($mentioned);
        $reply->unsetRelation('mentionsPosts');
    }

    protected function syncGroupMentions(Post $post, array $mentioned)
    {
        $post->mentionsGroups()->sync($mentioned);
        $post->unsetRelation('mentionsGroups');
    }

    protected function syncTagMentions(Post $post, array $mentioned)
    {
        $post->mentionsTags()->sync($mentioned);
        $post->unsetRelation('mentionsTags');
    }
}
