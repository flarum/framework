<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Discussion\Access;

use Flarum\Post\Post;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

class ScopeDiscussionVisibility
{
    public function __invoke(User $actor, Builder $query): void
    {
        if ($actor->cannot('viewForum')) {
            $query->whereRaw('FALSE');

            return;
        }

        // Hide private discussions by default.
        $query->where(function ($query) use ($actor) {
            $query->where('discussions.is_private', false)
            ->orWhere(function ($query) use ($actor) {
                $query->whereVisibleTo($actor, 'viewPrivate');
            });
        });

        // Hide hidden discussions, unless they are authored by the current
        // user, or the current user has permission to view hidden  discussions.
        if (! $actor->hasPermission('discussion.hide')) {
            $query->where(function ($query) use ($actor) {
                $query->whereNull('discussions.hidden_at')
                ->orWhere('discussions.user_id', $actor->id)
                    ->orWhere(function ($query) use ($actor) {
                        $query->whereVisibleTo($actor, 'hide');
                    });
            });
        }

        // Hide discussions with no comments, unless they are authored by the
        // current user, or the user is allowed to edit the discussion's posts.
        // comment_count leaves out private posts (e.g. awaiting approval), so
        // a private discussion also counts if it has a comment the actor may see.
        if (! $actor->hasPermission('discussion.editPosts')) {
            $query->where(function ($query) use ($actor) {
                $query->where('discussions.comment_count', '>', 0)
                    ->orWhere(function ($query) use ($actor) {
                        $query->where('discussions.is_private', true)
                            ->whereExists(function ($query) use ($actor) {
                                $query->selectRaw('1')
                                    ->from('posts')
                                    ->whereColumn('posts.discussion_id', 'discussions.id')
                                    ->where('posts.type', 'comment')
                                    ->whereNull('posts.hidden_at')
                                    ->where(function ($query) use ($actor) {
                                        $query->where('posts.is_private', false)
                                            ->orWhere(function ($query) use ($actor) {
                                                Post::query()->setQuery($query)->whereVisibleTo($actor, 'viewPrivate');
                                            });
                                    });
                            });
                    })
                    ->orWhere('discussions.user_id', $actor->id)
                    ->orWhere(function ($query) use ($actor) {
                        $query->whereVisibleTo($actor, 'editPosts');
                    });
            });
        }
    }
}
