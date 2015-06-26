<?php namespace Flarum\Subscriptions\Handlers;

use Flarum\Subscriptions\NewPostNotification;
use Flarum\Core\Events\PostWasPosted;
use Flarum\Core\Events\PostWasHidden;
use Flarum\Core\Events\PostWasRestored;
use Flarum\Core\Events\PostWasDeleted;
use Flarum\Core\Notifications\NotificationSyncer;
use Illuminate\Contracts\Events\Dispatcher;

class NewPostNotifier
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
        // Register with '1' as priority so this runs before discussion metadata
        // is updated, as we need to compare the user's last read number to that
        // of the previous post.
        $events->listen('Flarum\Core\Events\PostWasPosted', __CLASS__.'@whenPostWasPosted', 1);
        $events->listen('Flarum\Core\Events\PostWasHidden', __CLASS__.'@whenPostWasHidden');
        $events->listen('Flarum\Core\Events\PostWasRestored', __CLASS__.'@whenPostWasRestored');
        $events->listen('Flarum\Core\Events\PostWasDeleted', __CLASS__.'@whenPostWasDeleted');
    }

    public function whenPostWasPosted(PostWasPosted $event)
    {
        $post = $event->post;
        $discussion = $post->discussion;

        $notify = $discussion->readers()
            ->where('users.id', '!=', $post->user_id)
            ->where('users_discussions.subscription', 'follow')
            ->where('users_discussions.read_number', $discussion->last_post_number)
            ->get();

        $this->notifications->sync(
            $this->getNotification($event->post),
            $notify->all()
        );
    }

    public function whenPostWasHidden(PostWasHidden $event)
    {
        $this->notifications->delete($this->getNotification($event->post));
    }

    public function whenPostWasRestored(PostWasRestored $event)
    {
        $this->notifications->restore($this->getNotification($event->post));
    }

    public function whenPostWasDeleted(PostWasDeleted $event)
    {
        $this->notifications->delete($this->getNotification($event->post));
    }

    protected function getNotification($post)
    {
        return new NewPostNotification($post);
    }
}
