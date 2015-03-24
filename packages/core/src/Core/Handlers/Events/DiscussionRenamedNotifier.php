<?php namespace Flarum\Core\Handlers\Events;

use Flarum\Core\Events\DiscussionWasRenamed;
use Flarum\Core\Models\RenamedPost;
use Flarum\Core\Models\Notification;

class DiscussionRenamedNotifier
{
    /**
     * Register the listeners for the subscriber.
     *
     * @param  Illuminate\Events\Dispatcher  $events
     * @return array
     */
    public function subscribe($events)
    {
        $events->listen('Flarum\Core\Events\DiscussionWasRenamed', __CLASS__.'@whenDiscussionWasRenamed');
    }

    public function whenDiscussionWasRenamed(DiscussionWasRenamed $event)
    {
        $post = $this->createRenamedPost($event);

        $event->discussion->postWasAdded($post);

        $this->createRenamedNotification($event, $post);
    }

    protected function createRenamedPost(DiscussionWasRenamed $event)
    {
        $post = RenamedPost::reply(
            $event->discussion->id,
            $event->user->id,
            $event->oldTitle,
            $event->discussion->title
        );

        $post->save();

        return $post;
    }

    protected function createRenamedNotification(DiscussionWasRenamed $event, RenamedPost $post)
    {
        if ($event->discussion->start_user_id === $event->user->id) {
            return false;
        }

        $notification = Notification::notify(
            $event->discussion->start_user_id,
            'renamed',
            $event->user->id,
            $event->discussion->id,
            ['number' => $post->number, 'oldTitle' => $event->oldTitle]
        );

        $notification->save();

        return $notification;
    }
}
