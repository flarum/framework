<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Approval\Access;

use Closure;
use Flarum\Discussion\Discussion;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

class ScopePrivatePostVisibility
{
    /**
     * @param Builder $query
     * @param User $actor
     */
    public function __invoke(User $actor, Builder $query)
    {
        // All statements need to be wrapped in an orWhere, since we're adding a
        // subset of private posts that should be visible, not restricting the visible
        // set.
        $query->orWhere(function ($query) use ($actor) {
            // Show private posts if they require approval and they are
            // authored by the current user, or the current user has permission to
            // approve posts.
            $query->where('posts.is_approved', 0);

            if (! $actor->hasPermission('discussion.approvePosts')) {
                $query->where(function (Builder $query) use ($actor) {
                    $query->where('posts.user_id', $actor->id)
                        ->orWhereExists($this->discussionWhereCanApprovePosts($actor));
                });
            }
        });
    }

    /**
     * Looks if the actor has permission to approve posts,
     * within the discussion which the post is a part of.
     *
     * For example, the tags extension,
     * turns the `approvePosts` ability into per tag basis.
     */
    private function discussionWhereCanApprovePosts(User $actor): Closure
    {
        return function ($query) use ($actor) {
            $query->selectRaw('1')
                ->from('discussions')
                ->whereColumn('discussions.id', 'posts.discussion_id')
                ->where(function ($query) use ($actor) {
                    $query->whereRaw('1 != 1')->orWhere(function ($query) use ($actor) {
                        Discussion::query()->setQuery($query)->whereVisibleTo($actor, 'approvePosts');
                    });
                });
        };
    }
}
