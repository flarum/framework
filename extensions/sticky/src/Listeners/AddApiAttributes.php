<?php namespace Flarum\Sticky\Listeners;

use Flarum\Events\ApiAttributes;
use Flarum\Events\BuildApiAction;
use Illuminate\Contracts\Events\Dispatcher;
use Flarum\Api\Serializers\DiscussionSerializer;
use Flarum\Api\Actions\Discussions\IndexAction as DiscussionsIndexAction;

class AddApiAttributes
{
    public function subscribe(Dispatcher $events)
    {
        $events->listen(ApiAttributes::class, __CLASS__.'@addAttributes');
        $events->listen(BuildApiAction::class, __CLASS__.'@includeStartPost');
    }

    public function addAttributes(ApiAttributes $event)
    {
        if ($event->serializer instanceof DiscussionSerializer) {
            $event->attributes['isSticky'] = (bool) $event->model->is_sticky;
            $event->attributes['canSticky'] = (bool) $event->model->can($event->actor, 'sticky');
        }
    }
    public function includeStartPost(BuildApiAction $event)
    {
        if ($event->action instanceof DiscussionsIndexAction) {
            $event->addInclude('startPost');
        }
    }
}
