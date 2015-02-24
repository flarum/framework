<?php namespace Flarum\Core\Handlers\Events;

use Flarum\Core\Events\DiscussionWasRenamed;
use Flarum\Core\Models\RenamedPost;

class RenamedPostCreator
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
        $post = RenamedPost::reply(
            $event->discussion->id,
            $event->user->id,
            $event->oldTitle,
            $event->discussion->title
        );

        $post->save();

        $event->discussion->postWasAdded($post);
    }
}
