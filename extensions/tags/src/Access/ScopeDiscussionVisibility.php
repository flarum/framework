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

class ScopeDiscussionVisibility
{
    /**
     * @param User $actor
     * @param Builder $query
     */
    public function __invoke(User $actor, Builder $query)
    {
        // Hide discussions which have tags that the user is not allowed to see, unless an extension overrides this.
        $query->where(function ($query) use ($actor) {
            $query
                ->whereNotIn('discussions.id', function ($query) use ($actor) {
                    return $query->select('discussion_id')
                        ->from('discussion_tag')
                        ->whereIn('tag_id', Tag::getIdsWhereCannot($actor, 'viewDiscussions'));
                })
                ->orWhere(function ($query) use ($actor) {
                    $query->whereVisibleTo($actor, 'viewDiscussionsInRestrictedTags');
                });
        });

        // Hide discussions with no tags if the user doesn't have that global
        // permission.
        if (! $actor->hasPermission('viewDiscussions')) {
            $query->has('tags');
        }
    }
}
