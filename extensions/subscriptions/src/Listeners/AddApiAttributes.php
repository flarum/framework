<?php namespace Flarum\Subscriptions\Listeners;

use Flarum\Events\ApiAttributes;
use Illuminate\Contracts\Events\Dispatcher;
use Flarum\Api\Serializers\DiscussionSerializer;

class AddApiAttributes
{
    public function subscribe(Dispatcher $events)
    {
        $events->listen(ApiAttributes::class, __CLASS__.'@addAttributes');
    }

    public function addAttributes(ApiAttributes $event)
    {
        if ($event->serializer instanceof DiscussionSerializer &&
            ($state = $event->model->state)) {
            $event->attributes['subscription'] = $state->subscription ?: false;
        }
    }
}
