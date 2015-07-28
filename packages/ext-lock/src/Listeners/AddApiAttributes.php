<?php namespace Flarum\Lock\Listeners;

use Flarum\Events\ApiAttributes;
use Illuminate\Contracts\Events\Dispatcher;
use Flarum\Api\Serializers\DiscussionSerializer;

class AddApiAttributes
{
    public function subscribe(Dispatcher $events)
    {
        $events->listen(ApiAttributes::class, [$this, 'addAttributes']);
    }

    public function addAttributes(ApiAttributes $event)
    {
        if ($event->serializer instanceof DiscussionSerializer) {
            $event->attributes['isLocked'] = (bool) $event->model->is_locked;
            $event->attributes['canLock'] = (bool) $event->model->can($event->actor, 'lock');
        }
    }
}
