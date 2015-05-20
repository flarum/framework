<?php namespace Flarum\Categories\Handlers;

use Flarum\Categories\DiscussionMovedPost;
use Flarum\Categories\DiscussionMovedNotification;
use Flarum\Categories\Events\DiscussionWasMoved;
use Flarum\Core\Notifications\NotificationSyncer;
use Illuminate\Contracts\Events\Dispatcher;

class DiscussionMovedNotifier
{
    protected $notifications;

    public function __construct(NotificationSyncer $notifications)
    {
        $this->notifications = $notifications;
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param \Illuminate\Contracts\Events\Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen('Flarum\Categories\Events\DiscussionWasMoved', __CLASS__.'@whenDiscussionWasMoved');
    }

    public function whenDiscussionWasMoved(DiscussionWasMoved $event)
    {
        $post = DiscussionMovedPost::reply(
            $event->discussion->id,
            $event->user->id,
            $event->oldCategoryId,
            $event->discussion->category_id
        );

        $post = $event->discussion->addPost($post);

        if ($event->discussion->start_user_id !== $event->user->id) {
            $notification = new DiscussionMovedNotification($post);

            $this->notifications->sync($notification, $post->exists ? [$event->discussion->startUser] : []);
        }
    }
}
