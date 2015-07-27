<?php namespace Flarum\Tags\Listeners;

use Flarum\Events\RegisterDiscussionGambits;
use Illuminate\Contracts\Events\Dispatcher;

class AddTagGambit
{
    public function subscribe(Dispatcher $events)
    {
        $events->listen(RegisterDiscussionGambits::class, [$this, 'registerTagGambit']);
    }

    public function registerTagGambit(RegisterDiscussionGambits $event)
    {
        $event->gambits->add('Flarum\Tags\Gambits\TagGambit');
    }
}
