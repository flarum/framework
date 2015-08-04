<?php namespace Flarum\Pusher\Listeners;

use Flarum\Events\ApiAttributes;
use Illuminate\Contracts\Events\Dispatcher;
use Flarum\Api\Serializers\ForumSerializer;

class AddApiAttributes
{
    public function subscribe(Dispatcher $events)
    {
        $events->listen(ApiAttributes::class, [$this, 'addAttributes']);
    }

    public function addAttributes(ApiAttributes $event)
    {
        if ($event->serializer instanceof ForumSerializer) {
            $event->attributes['pusherKey'] = app('Flarum\Core\Settings\SettingsRepository')->get('pusher.app_key');
        }
    }
}
