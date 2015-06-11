<?php namespace Flarum\Tags\Handlers;

use Flarum\Tags\Tag;
use Flarum\Tags\TagSerializer;
use Flarum\Forum\Events\RenderView;

class TagPreloader
{
    public function subscribe($events)
    {
        $events->listen('Flarum\Forum\Events\RenderView', __CLASS__.'@renderForum');
    }

    public function renderForum(RenderView $event)
    {
        $serializer = new TagSerializer($event->action->actor, null, ['parent']);
        $event->view->data = array_merge($event->view->data, $serializer->collection(Tag::orderBy('position')->get())->toArray());
    }
}
