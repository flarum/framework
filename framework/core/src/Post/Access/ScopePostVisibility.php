<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Post\Access;

use Flarum\Discussion\Discussion;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

class ScopePostVisibility implements ScopesPostVisibilityWithinDiscussion
{
    /**
     * The fast path for posts of a single, already-authorized discussion: the
     * per-row discussion subqueries below collapse to constants.
     */
    public function withinDiscussion(User $actor, Builder $query, Discussion $discussion): void
    {
        // The caller vouches for the discussion being visible, so the
        // "discussion is visible" EXISTS is omitted entirely.

        // Hide private posts by default. (Per-post: stays in SQL.)
        $query->where(function ($query) use ($actor) {
            $query->where('posts.is_private', false)
                ->orWhere(function ($query) use ($actor) {
                    $query->whereVisibleTo($actor, 'viewPrivate');
                });
        });

        // Hide hidden posts unless the actor authored them or may moderate
        // this discussion. The policy check is the same one that governs the
        // actual hide/restore actions (core's global discussion.hidePosts
        // permission, per-tag moderator grants via the tags policy, admins),
        // evaluated once instead of per row.
        if (! $actor->can('hidePosts', $discussion)) {
            $query->where(function ($query) use ($actor) {
                $query->whereNull('posts.hidden_at')
                    ->orWhere('posts.user_id', $actor->id);
            });
        }
    }

    public function __invoke(User $actor, Builder $query): void
    {
        // Make sure the post's discussion is visible as well. Shaped as an
        // uncorrelated IN-subquery rather than a per-row EXISTS: the visible
        // discussions can then be materialized once per query, instead of the
        // discussion visibility conditions (which extensions compound) being
        // re-evaluated for every candidate post row — many posts share few
        // discussions, so on mention counts, post windows and search results
        // the EXISTS form did the same work hundreds of times over.
        $query->whereIn('posts.discussion_id', function ($query) use ($actor) {
            $query->select('discussions.id')->from('discussions');
            Discussion::query()->setQuery($query)->whereVisibleTo($actor);
        });

        // Hide private posts by default.
        $query->where(function ($query) use ($actor) {
            $query->where('posts.is_private', false)
                ->orWhere(function ($query) use ($actor) {
                    $query->whereVisibleTo($actor, 'viewPrivate');
                });
        });

        // Hide hidden posts, unless they are authored by the current user, or
        // the current user has permission to view hidden posts in the
        // discussion.
        if (! $actor->hasPermission('discussion.hidePosts')) {
            $query->where(function ($query) use ($actor) {
                $query->whereNull('posts.hidden_at')
                    ->orWhere('posts.user_id', $actor->id)
                    ->orWhereIn('posts.discussion_id', function ($query) use ($actor) {
                        $query->select('discussions.id')
                            ->from('discussions')
                            // The 1=0 seed keeps this subquery empty when no
                            // scoper grants the hidePosts ability at all.
                            ->where(function ($query) use ($actor) {
                                $query
                                    ->whereRaw('1=0')
                                    ->orWhere(function ($query) use ($actor) {
                                        Discussion::query()->setQuery($query)->whereVisibleTo($actor, 'hidePosts');
                                    });
                            });
                    });
            });
        }
    }
}
