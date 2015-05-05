<?php namespace Flarum\Categories\Handlers;

use Flarum\Categories\Category;
use Flarum\Categories\CategorySerializer;
use Flarum\Forum\Events\RenderView;

class CategoryPreloader
{
    public function subscribe($events)
    {
        $events->listen('Flarum\Forum\Events\RenderView', __CLASS__.'@renderForum');
    }

    public function renderForum(RenderView $event)
    {
        $serializer = new CategorySerializer($event->action->actor);
        $event->view->data = array_merge($event->view->data, $serializer->collection(Category::orderBy('position')->get())->toArray());
    }
}
