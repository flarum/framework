<?php namespace Flarum\Tags\Listeners;

use Flarum\Events\ScopeModelVisibility;
use Flarum\Events\ModelAllow;
use Flarum\Core\Discussions\Discussion;
use Flarum\Tags\Tag;

class ConfigureDiscussionPermissions
{
    public function subscribe($events)
    {
        $events->listen(ScopeModelVisibility::class, [$this, 'scopeDiscussionVisibility']);
        $events->listen(ModelAllow::class, [$this, 'allowDiscussionPermissions']);
    }

    public function scopeDiscussionVisibility(ScopeModelVisibility $event)
    {
        // Hide discussions which have tags that the user is not allowed to see.
        if ($event->model instanceof Discussion) {
            $event->query->whereNotExists(function ($query) use ($event) {
                return $query->select(app('flarum.db')->raw(1))
                    ->from('discussions_tags')
                    ->whereIn('tag_id', Tag::getNotVisibleTo($event->actor))
                    ->whereRaw('discussion_id = ' . app('flarum.db')->getQueryGrammar()->wrap('discussions.id'));
            });
        }
    }

    public function allowDiscussionPermissions(ModelAllow $event)
    {
        // Wrap all discussion permission checks with some logic pertaining to
        // the discussion's tags. If the discussion has a tag that has been
        // restricted, and the user has this permission for that tag, then they
        // are allowed. If the discussion only has tags that have been
        // restricted, then the user *must* have permission for at least one of
        // them.
        if ($event->model instanceof Discussion) {
            $tags = $event->model->tags;

            if (count($tags)) {
                $restricted = true;

                foreach ($tags as $tag) {
                    if ($tag->is_restricted) {
                        if ($event->actor->hasPermission('tag' . $tag->id . '.discussion.' . $event->action)) {
                            return true;
                        }
                    } else {
                        $restricted = false;
                    }
                }

                if ($restricted) {
                    return false;
                }
            }
        }
    }
}
