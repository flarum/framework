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

class ScopeDiscussionVisibilityForAbility
{
    /**
     * @param User $actor
     * @param Builder $query
     * @param string $ability
     */
    public function __invoke(User $actor, Builder $query, $ability)
    {
        if (substr($ability, 0, 4) === 'view') {
            return;
        }

        // If a discussion requires a certain permission in order for it to be
        // visible, then we can check if the user has been granted that
        // permission for any of the discussion's tags.
        $query->whereIn('discussions.id', function ($query) use ($actor, $ability) {
            return $query->select('discussion_id')
                ->from('discussion_tag')
                ->whereIn('tag_id', Tag::getIdsWhereCan($actor, 'discussion.'.$ability));
        });
    }
}
