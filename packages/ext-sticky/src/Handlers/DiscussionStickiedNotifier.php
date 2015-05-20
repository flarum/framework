<?php namespace Flarum\Sticky\Handlers;

use Flarum\Sticky\DiscussionStickiedPost;
use Flarum\Sticky\DiscussionStickiedNotification;
use Flarum\Sticky\Events\DiscussionWasStickied;
use Flarum\Sticky\Events\DiscussionWasUnstickied;
use Flarum\Core\Notifications\NotificationSyncer;
use Flarum\Core\Models\Discussion;
use Flarum\Core\Models\User;
use Illuminate\Contracts\Events\Dispatcher;

class DiscussionStickiedNotifier
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
        $events->listen('Flarum\Sticky\Events\DiscussionWasStickied', __CLASS__.'@whenDiscussionWasStickied');
        $events->listen('Flarum\Sticky\Events\DiscussionWasUnstickied', __CLASS__.'@whenDiscussionWasUnstickied');
    }

    public function whenDiscussionWasStickied(DiscussionWasStickied $event)
    {
        $this->stickyChanged($event->discussion, $event->user, true);
    }

    public function whenDiscussionWasUnstickied(DiscussionWasUnstickied $event)
    {
        $this->stickyChanged($event->discussion, $event->user, false);
    }

    protected function stickyChanged(Discussion $discussion, User $user, $isSticky)
    {
        $post = DiscussionStickiedPost::reply(
            $discussion->id,
            $user->id,
            $isSticky
        );

        $post = $discussion->addPost($post);

        if ($discussion->start_user_id !== $user->id) {
            $notification = new DiscussionStickiedNotification($post);

            $this->notifications->sync($notification, $post->exists ? [$discussion->startUser] : []);
        }
    }
}
