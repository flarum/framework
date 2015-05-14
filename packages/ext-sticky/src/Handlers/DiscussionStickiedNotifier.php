<?php namespace Flarum\Sticky\Handlers;

use Flarum\Sticky\DiscussionStickiedPost;
use Flarum\Sticky\DiscussionStickiedNotification;
use Flarum\Sticky\Events\DiscussionWasStickied;
use Flarum\Sticky\Events\DiscussionWasUnstickied;
use Flarum\Core\Notifications\Notifier;
use Illuminate\Contracts\Events\Dispatcher;

class DiscussionStickiedNotifier
{
    protected $notifier;

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
        $events->listen('Flarum\Sticky\Events\DiscussionWasStickied', __CLASS__.'@whenDiscussionWasStickied');
        $events->listen('Flarum\Sticky\Events\DiscussionWasUnstickied', __CLASS__.'@whenDiscussionWasUnstickied');
    }

    public function whenDiscussionWasStickied(DiscussionWasStickied $event)
    {
        $post = $this->createPost($event->discussion->id, $event->user->id, true);

        $post = $event->discussion->addPost($post);

        if ($event->discussion->start_user_id !== $event->user->id) {
            $notification = $this->createNotification($event->discussion, $post->user, $post);

            $this->notifier->send($notification, [$post->discussion->startUser]);
        }
    }

    public function whenDiscussionWasUnstickied(DiscussionWasUnstickied $event)
    {
        $post = $this->createPost($event->discussion->id, $event->user->id, false);

        $event->discussion->addPost($post);

        $this->notifier->retract($this->createNotification($event->discussion, $event->user));
    }

    protected function createPost($discussionId, $userId, $isSticky)
    {
        return DiscussionStickiedPost::reply(
            $discussionId,
            $userId,
            $isSticky
        );
    }

    protected function createNotification($discussion, $user, $post = null)
    {
        return new DiscussionStickiedNotification($discussion, $user, $post);
    }
}
