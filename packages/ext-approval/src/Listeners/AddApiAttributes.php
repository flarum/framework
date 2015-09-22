<?php namespace Flarum\Approval\Listeners;

use Flarum\Events\ApiAttributes;
use Flarum\Api\Serializers\DiscussionSerializer;
use Flarum\Api\Serializers\PostSerializer;
use Illuminate\Contracts\Events\Dispatcher;

class AddApiAttributes
{
    public function subscribe(Dispatcher $events)
    {
        $events->listen(ApiAttributes::class, [$this, 'addApiAttributes']);
    }

    public function addApiAttributes(ApiAttributes $event)
    {
        if ($event->serializer instanceof DiscussionSerializer ||
            $event->serializer instanceof PostSerializer) {
            $event->attributes['isApproved'] = (bool) $event->model->is_approved;
        }

        if ($event->serializer instanceof PostSerializer) {
            $event->attributes['canApprove'] = (bool) $event->model->discussion->can($event->actor, 'approvePosts');
        }
    }
}
