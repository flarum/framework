<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\Query;

use Flarum\Filter\FilterInterface;
use Flarum\Filter\FilterState;
use Flarum\Group\Group;
use Flarum\Search\AbstractRegexGambit;
use Flarum\Search\SearchState;
use Flarum\User\User;
use Illuminate\Database\Query\Builder;

class GroupFilterGambit extends AbstractRegexGambit implements FilterInterface
{
    /**
     * {@inheritdoc}
     */
    public function getGambitPattern()
    {
        return 'group:(.+)';
    }

    /**
     * {@inheritdoc}
     */
    protected function conditions(SearchState $search, array $matches, $negate)
    {
        $this->constrain($search->getQuery(), $search->getActor(), $matches[1], $negate);
    }

    public function getFilterKey(): string
    {
        return 'group';
    }

    public function filter(FilterState $filterState, string $filterValue, bool $negate)
    {
        $this->constrain($filterState->getQuery(), $filterState->getActor(), $filterValue, $negate);
    }

    protected function constrain(Builder $query, User $actor, string $rawQuery, bool $negate)
    {
        $groupIdentifiers = explode(',', trim($rawQuery, '"'));

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

        $groupQuery->whereIn('id', $ids)
            ->orWhereIn('name_singular', $names)
            ->orWhereIn('name_plural', $names);

        $userIds = $groupQuery->join('group_user', 'groups.id', 'group_user.group_id')
            ->pluck('group_user.user_id')
            ->all();

        $query->whereIn('id', $userIds, 'and', $negate);
    }
}
