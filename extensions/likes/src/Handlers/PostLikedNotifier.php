<?php namespace Flarum\Likes\Handlers;

use Flarum\Likes\PostLikedNotification;
use Flarum\Likes\Events\PostWasLiked;
use Flarum\Likes\Events\PostWasUnliked;
use Flarum\Core\Notifications\NotificationSyncer;
use Illuminate\Contracts\Events\Dispatcher;

class PostLikedNotifier
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
        $events->listen('Flarum\Likes\Events\PostWasLiked', __CLASS__.'@whenPostWasLiked');
        $events->listen('Flarum\Likes\Events\PostWasUnliked', __CLASS__.'@whenPostWasUnliked');
    }

    public function whenPostWasLiked(PostWasLiked $event)
    {
        if ($event->post->user->id != $event->user->id) {
            $this->sync($event->post, $event->user, [$event->post->user]);
        }
    }

    public function whenPostWasUnliked(PostWasUnliked $event)
    {
        if ($event->post->user->id != $event->user->id) {
            $this->sync($event->post, $event->user, []);
        }
    }

    public function sync($post, $user, array $recipients)
    {
        $this->notifications->sync(
            new PostLikedNotification($post, $user),
            $recipients
        );
    }
}
