<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Discussion\Search\Filter;

use Flarum\Group\Group;
use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;
use Flarum\Search\ValidateFilterTrait;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Discussions started by a member of any of the given groups, by ID or name.
 *
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
        $groupIds = $this->groupIds($state->getActor(), $this->asStringArray($value));

        $startedByMember = function (Builder $query) use ($groupIds) {
            // Every registered user is a member, though none is listed in group_user.
            if (in_array(Group::MEMBER_ID, $groupIds)) {
                $query->whereNotNull('discussions.user_id');
            } else {
                $query->whereIn('discussions.user_id', function (QueryBuilder $query) use ($groupIds) {
                    $query->select('user_id')->from('group_user')->whereIn('group_id', $groupIds);
                });
            }
        };

        if ($negate) {
            // A discussion whose author was deleted wasn't started by a member.
            $state->getQuery()->where(function (Builder $query) use ($startedByMember) {
                $query->whereNull('discussions.user_id')->orWhereNot($startedByMember);
            });
        } else {
            $state->getQuery()->where($startedByMember);
        }
    }

    /**
     * @param string[] $identifiers
     * @return int[]
     */
    protected function groupIds(User $actor, array $identifiers): array
    {
        $ids = array_map('intval', array_filter($identifiers, 'is_numeric'));
        $names = array_map('mb_strtolower', $identifiers);

        // Names are compared here rather than in SQL, where lower() only folds
        // ASCII on some databases. There are only ever a handful of groups.
        return Group::whereVisibleTo($actor)
            ->get()
            ->filter(fn (Group $group) => in_array($group->id, $ids, true)
                || in_array(mb_strtolower($group->name_singular), $names, true)
                || in_array(mb_strtolower($group->name_plural), $names, true))
            ->map(fn (Group $group) => $group->id)
            ->values()
            ->all();
    }
}
