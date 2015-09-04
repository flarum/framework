<?php namespace Flarum\Tags\Listeners;

use Flarum\Events\ScopeModelVisibility;
use Flarum\Events\ModelAllow;
use Flarum\Tags\Tag;

class ConfigureTagPermissions
{
    public function subscribe($events)
    {
        $events->listen(ScopeModelVisibility::class, [$this, 'scopeTagVisibility']);
        $events->listen(ModelAllow::class, [$this, 'allowStartDiscussion']);
    }

    public function scopeTagVisibility(ScopeModelVisibility $event)
    {
        if ($event->model instanceof Tag) {
            $event->query->whereNotIn('id', Tag::getIdsWhereCannot($event->actor, 'view'));
        }
    }

    public function allowStartDiscussion(ModelAllow $event)
    {
        if ($event->model instanceof Tag) {
            if (! $event->model->is_restricted ||
                $event->actor->hasPermission('tag' . $event->model->id . '.startDiscussion')) {
                return true;
            }
        }
    }
}
