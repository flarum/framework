<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Access;

use Flarum\Tags\Tag;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ScopeDiscussionVisibilityForAbility
{
    /**
     * @param User $actor
     * @param Builder $query
     * @param string $ability
     */
    public function __invoke(User $actor, Builder $query, $ability)
    {
        // Automatic scoping should be applied to the global `view` ability,
        // and to arbitrary abilities that aren't subqueries of `view`.
        // For example, if we want to scope discussions where the user can
        // edit posts, this should apply.
        // But if we are expanding a restriction of `view` (for example,
        // `viewPrivate`), we shouldn't apply this query again.
        if (substr($ability, 0, 4) === 'view' && $ability !== 'view') {
            return;
        }

        // Avoid an infinite recursive loop.
        if (Str::endsWith($ability, 'InRestrictedTags')) {
            return;
        }

        // `view` is a special case where the permission string is represented by `viewForum`.
        $permission = $ability === 'view' ? 'viewForum' : $ability;

        // Restrict discussions where users don't have necessary permissions in all tags.
        // We use a double notIn instead of a doubleIn because the permission must be present in ALL tags,
        // not just one.
        $query->where(function ($query) use ($actor, $permission) {
            $query
                ->whereNotIn('discussions.id', function ($query) use ($actor, $permission) {
                    return $query->select('discussion_id')
                        ->from('discussion_tag')
                        ->whereNotIn('tag_id', function ($query) use ($actor, $permission) {
                            Tag::query()->setQuery($query->from('tags'))->whereHasPermission($actor, $permission)->select('tags.id');
                        });
                })
                ->orWhere(function ($query) use ($actor, $permission) {
                    // Allow extensions a way to override scoping for any given permission.
                    $query->whereVisibleTo($actor, "${permission}InRestrictedTags");
                });
        });

        // Hide discussions with no tags if the user doesn't have that global
        // permission.
        if (! $actor->hasPermission($permission)) {
            $query->has('tags');
        }
    }
}
