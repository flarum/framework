<?php namespace Flarum\Tags\Handlers;

use Flarum\Tags\DiscussionTaggedPost;
use Flarum\Tags\Events\DiscussionWasTagged;
use Flarum\Core\Notifications\NotificationSyncer;
use Illuminate\Contracts\Events\Dispatcher;

class DiscussionTaggedNotifier
{
    /**
     * Register the listeners for the subscriber.
     *
     * @param \Illuminate\Contracts\Events\Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen('Flarum\Tags\Events\DiscussionWasTagged', __CLASS__.'@whenDiscussionWasTagged');
    }

    public function whenDiscussionWasTagged(DiscussionWasTagged $event)
    {
        $post = DiscussionTaggedPost::reply(
            $event->discussion->id,
            $event->user->id,
            array_pluck($event->oldTags, 'id'),
            $event->discussion->tags()->lists('id')
        );

        $post = $event->discussion->addPost($post);
    }
}
