<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Approval\Access;

use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

class ScopePrivateDiscussionVisibility
{
    /**
     * @param Builder $query
     * @param User $actor
     */
    public function __invoke(User $actor, Builder $query)
    {
        // All statements need to be wrapped in an orWhere, since we're adding a
        // subset of private discussions that should be visible, not restricting the visible
        // set.
        $query->orWhere(function ($query) use ($actor) {
            // Show empty/private discussions if they require approval and they are
            // authored by the current user, or the current user has permission to
            // approve posts.
            $query->where('discussions.is_approved', 0);

            if (! $actor->hasPermission('discussion.approvePosts')) {
                $query->where(function (Builder $query) use ($actor) {
                    $query->where('discussions.user_id', $actor->id)
                        ->orWhere(function ($query) use ($actor) {
                            $query->whereVisibleTo($actor, 'approvePosts');
                        });
                });
            }
        });
    }
}
