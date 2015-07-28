<?php namespace Flarum\Lock\Listeners;

use Flarum\Events\ModelAllow;
use Flarum\Core\Discussions\Discussion;

class ConfigurePermissions
{
    public function subscribe($events)
    {
        $events->listen(ModelAllow::class, [$this, 'allowDiscussionPermissions'], 10);
    }

    public function allowDiscussionPermissions(ModelAllow $event)
    {
        if ($event->model instanceof Discussion &&
            $event->model->is_locked &&
            $event->action === 'reply') {
            if (! $event->model->can($event->actor, 'lock')) {
                return false;
            }
        }
    }
}
