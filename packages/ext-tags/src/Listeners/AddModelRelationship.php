<?php namespace Flarum\Tags\Listeners;

use Flarum\Events\ModelRelationship;
use Flarum\Core\Discussions\Discussion;
use Flarum\Tags\Tag;

class AddModelRelationship
{
    public function subscribe($events)
    {
        $events->listen(ModelRelationship::class, __CLASS__.'@addTagsRelationship');
    }

    public function addTagsRelationship(ModelRelationship $event)
    {
        if ($event->model instanceof Discussion &&
            $event->relationship === 'tags') {
            return $event->model->belongsToMany('Flarum\Tags\Tag', 'discussions_tags', null, null, 'tags');
        }
    }
}
