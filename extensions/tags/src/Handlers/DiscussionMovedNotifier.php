<?php namespace Flarum\Categories\Handlers;

use Flarum\Categories\DiscussionMovedPost;
use Flarum\Categories\DiscussionMovedNotification;
use Flarum\Categories\Events\DiscussionWasMoved;
use Flarum\Core\Notifications\Notifier;
use Illuminate\Contracts\Events\Dispatcher;

class DiscussionMovedNotifier
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
        $events->listen('Flarum\Categories\Events\DiscussionWasMoved', __CLASS__.'@whenDiscussionWasMoved');
    }

    public function whenDiscussionWasMoved(DiscussionWasMoved $event)
    {
        $post = $this->createPost($event);

        $post = $event->discussion->addPost($post);

        if ($event->discussion->start_user_id !== $event->user->id) {
            $notification = new DiscussionMovedNotification($event->discussion, $post->user, $post);

            if ($post->exists) {
                $this->notifier->send($notification, [$post->discussion->startUser]);
            } else {
                $this->notifier->retract($notification);
            }
        }
    }

    protected function createPost(DiscussionWasMoved $event)
    {
        return DiscussionMovedPost::reply(
            $event->discussion->id,
            $event->user->id,
            $event->oldCategoryId,
            $event->discussion->category_id
        );
    }
}
