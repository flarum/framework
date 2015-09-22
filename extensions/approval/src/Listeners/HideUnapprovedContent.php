<?php namespace Flarum\Approval\Listeners;

use Flarum\Events\ScopeModelVisibility;
use Flarum\Events\ScopePostVisibility;
use Flarum\Events\ScopeHiddenDiscussionVisibility;
use Flarum\Core\Discussions\Discussion;
use Illuminate\Contracts\Events\Dispatcher;

class HideUnapprovedContent
{
    public function subscribe(Dispatcher $events)
    {
        $events->listen(ScopeModelVisibility::class, [$this, 'hideUnapprovedDiscussions']);
        $events->listen(ScopePostVisibility::class, [$this, 'hideUnapprovedPosts']);
    }

    public function hideUnapprovedDiscussions(ScopeModelVisibility $event)
    {
        if ($event->model instanceof Discussion) {
            $user = $event->actor;

            if (! $user->hasPermission('discussion.editPosts')) {
                $event->query->where(function ($query) use ($user) {
                    $query->where('discussions.is_approved', 1)
                        ->orWhere('start_user_id', $user->id);

                    event(new ScopeHiddenDiscussionVisibility($query, $user, 'discussion.editPosts'));
                });
            }
        }
    }

    public function hideUnapprovedPosts(ScopePostVisibility $event)
    {
        if ($event->discussion->can($event->actor, 'editPosts')) {
            return;
        }

        $event->query->where(function ($query) use ($event) {
            $query->where('posts.is_approved', 1)
                ->orWhere('user_id', $event->actor->id);
        });
    }
}
