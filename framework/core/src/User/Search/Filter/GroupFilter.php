<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\Search\Filter;

use Flarum\Group\Group;
use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;
use Flarum\Search\ValidateFilterTrait;
use Flarum\User\User;
use Illuminate\Database\Query\Builder;

/**
 * @implements FilterInterface<DatabaseSearchState>
 */
class GroupFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function getFilterKey(): string
    {
        return 'group';
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        $this->constrain($state->getQuery(), $state->getActor(), $value, $negate);
    }

    protected function constrain(Builder $query, User $actor, string|array $rawQuery, bool $negate): void
    {
        $groupIdentifiers = $this->asStringArray($rawQuery);

        $ids = [];
        $names = [];
        foreach ($groupIdentifiers as $identifier) {
            if (is_numeric($identifier)) {
                $ids[] = $identifier;
            } else {
                $names[] = $identifier;
            }
        }

        $groupQuery = Group::whereVisibleTo($actor)
            ->join('group_user', 'groups.id', 'group_user.group_id')
            ->where(function (\Illuminate\Database\Eloquent\Builder $query) use ($ids, $names) {
                $query->whereIn('groups.id', $ids)
                    ->orWhereIn($query->raw('lower(name_singular)'), $names)
                    ->orWhereIn($query->raw('lower(name_plural)'), $names);
            });

        $userIds = $groupQuery
            ->pluck('group_user.user_id')
            ->all();

        $query->whereIn('id', $userIds, 'and', $negate);
    }
}
