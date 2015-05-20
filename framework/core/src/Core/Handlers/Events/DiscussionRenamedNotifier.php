<?php namespace Flarum\Core\Handlers\Events;

use Flarum\Core\Events\DiscussionWasRenamed;
use Flarum\Core\Models\DiscussionRenamedPost;
use Flarum\Core\Notifications\DiscussionRenamedNotification;
use Flarum\Core\Notifications\NotificationSyncer;
use Illuminate\Contracts\Events\Dispatcher;

class DiscussionRenamedNotifier
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
        $events->listen('Flarum\Core\Events\DiscussionWasRenamed', __CLASS__.'@whenDiscussionWasRenamed');
    }

    public function whenDiscussionWasRenamed(DiscussionWasRenamed $event)
    {
        $post = DiscussionRenamedPost::reply(
            $event->discussion->id,
            $event->user->id,
            $event->oldTitle,
            $event->discussion->title
        );

        $post = $event->discussion->addPost($post);

        if ($event->discussion->start_user_id !== $event->user->id) {
            $notification = new DiscussionRenamedNotification($post);

            $this->notifications->sync($notification, $post->exists ? [$event->discussion->startUser] : []);
        }
    }
}
