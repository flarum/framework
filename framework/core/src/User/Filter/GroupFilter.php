<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\Filter;

use Flarum\Filter\FilterInterface;
use Flarum\Filter\FilterState;
use Flarum\Filter\ValidateFilterTrait;
use Flarum\Group\Group;
use Flarum\User\User;
use Illuminate\Database\Query\Builder;

class GroupFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function getFilterKey(): string
    {
        return 'group';
    }

    public function filter(FilterState $filterState, string|array $filterValue, bool $negate): void
    {
        $this->constrain($filterState->getQuery(), $filterState->getActor(), $filterValue, $negate);
    }

    protected function constrain(Builder $query, User $actor, string|array $rawQuery, bool $negate): void
    {
        $groupIdentifiers = $this->asStringArray($rawQuery);
        $groupQuery = Group::whereVisibleTo($actor);

        $ids = [];
        $names = [];
        foreach ($groupIdentifiers as $identifier) {
            if (is_numeric($identifier)) {
                $ids[] = $identifier;
            } else {
                $names[] = $identifier;
            }
        }

        $groupQuery->whereIn('groups.id', $ids)
            ->orWhereIn('name_singular', $names)
            ->orWhereIn('name_plural', $names);

        $userIds = $groupQuery->join('group_user', 'groups.id', 'group_user.group_id')
            ->pluck('group_user.user_id')
            ->all();

        $query->whereIn('id', $userIds, 'and', $negate);
    }
}
