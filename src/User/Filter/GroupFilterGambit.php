<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\Filter;

use Flarum\Filter\FilterInterface;
use Flarum\Filter\WrappedFilter;
use Flarum\Group\Group;
use Flarum\Search\AbstractRegexGambit;
use Flarum\Search\AbstractSearch;
use Flarum\User\User;
use Illuminate\Database\Query\Builder;

class GroupFilterGambit extends AbstractRegexGambit implements FilterInterface
{/**
     * {@inheritdoc}
     */
    protected $pattern = 'group:(.+)';

    /**
     * {@inheritdoc}
     */
    protected function conditions(AbstractSearch $search, array $matches, $negate)
    {
        $this->constrain($search->getQuery(), $search->getActor(), $matches[1], $negate);
    }

    public function getFilterKey(): string
    {
        return 'group';
    }

    public function filter(WrappedFilter $wrappedFilter, string $filterValue, bool $negate)
    {
        $this->constrain($wrappedFilter->getQuery(), $wrappedFilter->getActor(), $filterValue, $negate);
    }

    protected function constrain(Builder $query, User $actor, string $rawQuery, bool $negate)
    {
        $groupIdentifiers = explode(',', trim($rawQuery, '"'));

        $groupQuery = Group::whereVisibleTo($actor);

        foreach ($groupIdentifiers as $identifier) {
            if (is_numeric($identifier)) {
                $groupQuery->orWhere('id', $identifier);
            } else {
                $groupQuery->orWhere('name_singular', $identifier)->orWhere('name_plural', $identifier);
            }
        }

        $userIds = $groupQuery->join('group_user', 'groups.id', 'group_user.group_id')
            ->pluck('group_user.user_id')
            ->all();

        $query->whereIn('id', $userIds, 'and', $negate);
    }
}
