<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Mentions\Listener;

use Flarum\Api\Serializer\PostSerializer;
use Flarum\Event\ConfigureNotificationTypes;
use Flarum\Mentions\Notification\PostMentionedBlueprint;
use Flarum\Notification\NotificationSyncer;
use Flarum\Post\Event\Deleted;
use Flarum\Post\Event\Hidden;
use Flarum\Post\Event\Posted;
use Flarum\Post\Event\Restored;
use Flarum\Post\Event\Revised;
use Flarum\Post\Post;
use Illuminate\Contracts\Events\Dispatcher;
use s9e\TextFormatter\Utils;

class UpdatePostMentionsMetadata
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
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(ConfigureNotificationTypes::class, [$this, 'addNotificationType']);
        $events->listen(Posted::class, [$this, 'whenPosted']);
        $events->listen(Revised::class, [$this, 'whenRevised']);
        $events->listen(Hidden::class, [$this, 'whenHidden']);
        $events->listen(Restored::class, [$this, 'whenRestored']);
        $events->listen(Deleted::class, [$this, 'whenDeleted']);
    }

    /**
     * @param ConfigureNotificationTypes $event
     */
    public function addNotificationType(ConfigureNotificationTypes $event)
    {
        $event->add(PostMentionedBlueprint::class, PostSerializer::class, ['alert']);
    }

    /**
     * @param Posted $event
     */
    public function whenPosted(Posted $event)
    {
        $this->replyBecameVisible($event->post);
    }

    /**
     * @param Revised $event
     */
    public function whenRevised(Revised $event)
    {
        $this->replyBecameVisible($event->post);
    }

    /**
     * @param Hidden $event
     */
    public function whenHidden(Hidden $event)
    {
        $this->replyBecameInvisible($event->post);
    }

    /**
     * @param Restored $event
     */
    public function whenRestored(Restored $event)
    {
        $this->replyBecameVisible($event->post);
    }

    /**
     * @param Deleted $event
     */
    public function whenDeleted(Deleted $event)
    {
        $this->replyBecameInvisible($event->post);
    }

    /**
     * @param Post $reply
     */
    protected function replyBecameVisible(Post $reply)
    {
        $mentioned = Utils::getAttributeValues($reply->parsedContent, 'POSTMENTION', 'id');

        $this->sync($reply, $mentioned);
    }

    /**
     * @param Post $reply
     */
    protected function replyBecameInvisible(Post $reply)
    {
        $this->sync($reply, []);
    }

    /**
     * @param Post $reply
     * @param array $mentioned
     */
    protected function sync(Post $reply, array $mentioned)
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
