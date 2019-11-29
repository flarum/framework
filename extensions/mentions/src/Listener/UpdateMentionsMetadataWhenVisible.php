<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Listener;

use Flarum\Mentions\Notification\PostMentionedBlueprint;
use Flarum\Mentions\Notification\UserMentionedBlueprint;
use Flarum\Notification\NotificationSyncer;
use Flarum\Post\Event\Posted;
use Flarum\Post\Event\Restored;
use Flarum\Post\Event\Revised;
use Flarum\Post\Post;
use Flarum\User\User;
use s9e\TextFormatter\Utils;

class UpdateMentionsMetadataWhenVisible
{
    /**
     * @var NotificationSyncer
     */
    protected $notifications;

    /**
     * @param NotificationSyncer $notifications
     */
    public function __construct(NotificationSyncer $notifications)
    {
        $this->notifications = $notifications;
    }

    /**
     * @param Posted|Restored|Revised $event
     */
    public function handle($event)
    {
        $content = $event->post->parsedContent;

        $this->syncUserMentions(
            $event->post,
            Utils::getAttributeValues($content, 'USERMENTION', 'id')
        );

        $this->syncPostMentions(
            $event->post,
            Utils::getAttributeValues($content, 'POSTMENTION', 'id')
        );
    }

    protected function syncUserMentions(Post $post, array $mentioned)
    {
        $post->mentionsUsers()->sync($mentioned);

        $users = User::whereIn('id', $mentioned)
            ->get()
            ->filter(function ($user) use ($post) {
                return $post->isVisibleTo($user) && $user->id !== $post->user->id;
            })
            ->all();

        $this->notifications->sync(new UserMentionedBlueprint($post), $users);
    }

    protected function syncPostMentions(Post $reply, array $mentioned)
    {
        $reply->mentionsPosts()->sync($mentioned);

        $posts = Post::with('user')
            ->whereIn('id', $mentioned)
            ->get()
            ->filter(function ($post) use ($reply) {
                return $post->user && $post->user->id !== $reply->user_id;
            })
            ->all();

        foreach ($posts as $post) {
            $this->notifications->sync(new PostMentionedBlueprint($post, $reply), [$post->user]);
        }
    }
}
