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
    public function __invoke(User $actor, Builder $query, string $ability): void
    {
        // Automatic scoping should be applied to the global `view` ability,
        // and to arbitrary abilities that aren't subqueries of `view`.
        // For example, if we want to scope discussions where the user can
        // edit posts, this should apply.
        // But if we are expanding a restriction of `view` (for example,
        // `viewPrivate`), we shouldn't apply this query again.
        if (Str::startsWith($ability, 'view') && $ability !== 'view') {
            return;
        }

        // Avoid an infinite recursive loop.
        if (Str::endsWith($ability, 'InRestrictedTags')) {
            return;
        }

        // `view` is a special case where the permission string is represented by `viewForum`.
        $permission = $ability === 'view' ? 'viewForum' : $ability;

        $hasGlobalPermission = $actor->hasPermission($permission) || $actor->hasPermission("discussion.$permission");

        // Without the global permission, an ability other than `view` follows
        // the tags DiscussionPolicy: only the discussion's restricted tags
        // count, and at least one of them must grant it. An open parent
        // doesn't block a granted restricted child, but a restricted parent
        // must grant it too, which the policy doesn't check. `view` keeps
        // requiring the permission in every tag.
        $onlyRestrictedTags = $ability !== 'view' && ! $hasGlobalPermission;

        $permittedTagIds = function ($query) use ($actor, $permission, $onlyRestrictedTags) {
            Tag::query()->setQuery($query->from('tags'))->whereHasPermission($actor, $permission, $onlyRestrictedTags)->select('tags.id');
        };

        // Exclude discussions with a tag (only a restricted one, in that mode)
        // that doesn't grant the permission: a double notIn, because every
        // such tag must grant it, not just one.
        $query->where(function ($query) use ($actor, $permission, $onlyRestrictedTags, $permittedTagIds) {
            $query
                ->where(function ($query) use ($onlyRestrictedTags, $permittedTagIds) {
                    $query->whereNotIn('discussions.id', function ($query) use ($onlyRestrictedTags, $permittedTagIds) {
                        $query->select('discussion_id')
                            ->from('discussion_tag')
                            ->whereNotIn('tag_id', $permittedTagIds)
                            ->when($onlyRestrictedTags, fn ($query) => $query->whereIn('tag_id', function ($query) {
                                $query->select('id')->from('tags')->where('is_restricted', true);
                            }));
                    });

                    if ($onlyRestrictedTags) {
                        $query->whereIn('discussions.id', function ($query) use ($permittedTagIds) {
                            $query->select('discussion_id')
                                ->from('discussion_tag')
                                ->whereIn('tag_id', $permittedTagIds);
                        });
                    }
                })
                ->orWhere(function ($query) use ($actor, $permission) {
                    // Allow extensions a way to override scoping for any given permission.
                    $query->whereVisibleTo($actor, "{$permission}InRestrictedTags");
                });
        });

        // Hide discussions with no tags if the user doesn't have that global
        // permission.
        if (! $hasGlobalPermission) {
            $query->has('tags');
        }
    }
}
