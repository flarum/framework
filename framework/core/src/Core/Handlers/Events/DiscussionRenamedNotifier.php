<?php namespace Flarum\Core\Handlers\Events;

use Flarum\Core\Events\DiscussionWasRenamed;
use Flarum\Core\Models\DiscussionRenamedPost;
use Flarum\Core\Notifications\Types\DiscussionRenamedNotification;
use Flarum\Core\Notifications\Notifier;
use Illuminate\Contracts\Events\Dispatcher;

class DiscussionRenamedNotifier
{
    public function __construct(Notifier $notifier)
    {
        $this->notifier = $notifier;
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
        $post = $this->createPost($event);

        $event->discussion->addPost($post);

        if ($event->discussion->start_user_id !== $event->user->id) {
            $this->sendNotification($event, $post);
        }
    }

    protected function createPost(DiscussionWasRenamed $event)
    {
        return DiscussionRenamedPost::reply(
            $event->discussion->id,
            $event->user->id,
            $event->oldTitle,
            $event->discussion->title
        );
    }

    protected function sendNotification(DiscussionWasRenamed $event, DiscussionRenamedPost $post)
    {
        $notification = new DiscussionRenamedNotification(
            $event->discussion->startUser,
            $event->user,
            $post,
            $event->oldTitle
        );

        $this->notifier->send($notification);
    }
}
