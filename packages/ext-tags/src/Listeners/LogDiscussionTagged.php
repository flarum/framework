<?php namespace Flarum\Tags\Listeners;

use Flarum\Events\RegisterPostTypes;
use Flarum\Tags\Posts\DiscussionTaggedPost;
use Flarum\Tags\Events\DiscussionWasTagged;
use Illuminate\Contracts\Events\Dispatcher;

class LogDiscussionTagged
{
    public function subscribe(Dispatcher $events)
    {
        $events->listen(RegisterPostTypes::class, [$this, 'registerPostType']);
        $events->listen(DiscussionWasTagged::class, [$this, 'whenDiscussionWasTagged']);
    }

    public function registerPostType(RegisterPostTypes $event)
    {
        $event->register(DiscussionTaggedPost::class);
    }

    public function whenDiscussionWasTagged(DiscussionWasTagged $event)
    {
        $post = DiscussionTaggedPost::reply(
            $event->discussion->id,
            $event->user->id,
            array_pluck($event->oldTags, 'id'),
            $event->discussion->tags()->lists('id')
        );

        $event->discussion->mergePost($post);
    }
}
