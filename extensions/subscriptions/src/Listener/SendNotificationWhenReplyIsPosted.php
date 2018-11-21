<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Subscriptions\Listener;

use Flarum\Api\Serializer\BasicDiscussionSerializer;
use Flarum\Event\ConfigureNotificationTypes;
use Flarum\Notification\NotificationSyncer;
use Flarum\Post\Event\Deleted;
use Flarum\Post\Event\Hidden;
use Flarum\Post\Event\Posted;
use Flarum\Post\Event\Restored;
use Flarum\Post\Post;
use Flarum\Subscriptions\Notification\NewPostBlueprint;
use Illuminate\Contracts\Events\Dispatcher;

class SendNotificationWhenReplyIsPosted
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
        $events->listen(Hidden::class, [$this, 'whenHidden']);
        $events->listen(Restored::class, [$this, 'whenRestored']);
        $events->listen(Deleted::class, [$this, 'whenDeleted']);
    }

    /**
     * @param ConfigureNotificationTypes $event
     */
    public function addNotificationType(ConfigureNotificationTypes $event)
    {
        $event->add(NewPostBlueprint::class, BasicDiscussionSerializer::class, ['alert', 'email']);
    }

    /**
     * @param Posted $event
     */
    public function whenPosted(Posted $event)
    {
        $post = $event->post;
        $discussion = $post->discussion;

        $notify = $discussion->readers()
            ->where('users.id', '!=', $post->user_id)
            ->where('discussion_user.subscription', 'follow')
            ->where('discussion_user.last_read_post_number', $discussion->last_post_number)
            ->get();

        $this->notifications->sync(
            $this->getNotification($event->post),
            $notify->all()
        );
    }

    /**
     * @param Hidden $event
     */
    public function whenHidden(Hidden $event)
    {
        $this->notifications->delete($this->getNotification($event->post));
    }

    /**
     * @param Restored $event
     */
    public function whenRestored(Restored $event)
    {
        $this->notifications->restore($this->getNotification($event->post));
    }

    /**
     * @param Deleted $event
     */
    public function whenDeleted(Deleted $event)
    {
        $this->notifications->delete($this->getNotification($event->post));
    }

    /**
     * @param Post $post
     * @return NewPostBlueprint
     */
    protected function getNotification(Post $post)
    {
        return new NewPostBlueprint($post);
    }
}
