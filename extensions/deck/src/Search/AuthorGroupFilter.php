<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Deck\Search;

use Flarum\Group\Group;
use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;
use Flarum\Search\ValidateFilterTrait;
use Illuminate\Database\Query\Builder;

/**
 * Posts by members of the given groups, for Deck's group columns.
 *
 * Only groups the actor can see count: a hidden group's membership is itself
 * hidden, so filtering by it must not reveal who is in it. Members is implicit
 * in Flarum (nobody has a group_user row for it), so it means any registered
 * user.
 *
 * @implements FilterInterface<DatabaseSearchState>
 */
class AuthorGroupFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function getFilterKey(): string
    {
        return 'authorGroup';
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        $query = $state->getQuery();

        $groupIds = Group::whereVisibleTo($state->getActor())
            ->whereIn('id', $this->asIntArray($value))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (! $groupIds) {
            $query->whereRaw($negate ? '1 = 1' : '0 = 1');

            return;
        }

        if (in_array(Group::MEMBER_ID, $groupIds, true)) {
            $negate ? $query->whereNull('posts.user_id') : $query->whereNotNull('posts.user_id');

            return;
        }

        $query->whereIn(
            'posts.user_id',
            fn (Builder $members) => $members->select('user_id')->from('group_user')->whereIn('group_id', $groupIds),
            'and',
            $negate
        );
    }
}
