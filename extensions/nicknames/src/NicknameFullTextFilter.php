<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Nicknames;

use Flarum\Search\AbstractFulltextFilter;
use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\SearchState;
use Flarum\User\UserRepository;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends AbstractFulltextFilter<DatabaseSearchState>
 */
class NicknameFullTextFilter extends AbstractFulltextFilter
{
    public function __construct(
        protected UserRepository $users
    ) {
    }

    private function getUserSearchSubQuery(string $searchValue): Builder
    {
        return $this->users
            ->query()
            ->select('id')
            ->where('username', 'like', "%{$searchValue}%")
            ->orWhere('nickname', 'like', "%{$searchValue}%");
    }

    /**
     * Matches the text anywhere in a username or nickname, with names that
     * start with it first.
     */
    public function search(SearchState $state, string $value): void
    {
        $state->getQuery()
            ->whereIn(
                'id',
                $this->getUserSearchSubQuery($value)
            );

        $state->setDefaultSort(function (Builder $query) use ($value) {
            $grammar = $query->getGrammar();
            $username = $grammar->wrap('users.username');
            $nickname = $grammar->wrap('users.nickname');

            $query
                ->orderByRaw("CASE WHEN LOWER($username) LIKE ? OR LOWER($nickname) LIKE ? THEN 0 ELSE 1 END", array_fill(0, 2, mb_strtolower($value).'%'))
                ->orderByRaw("COALESCE($nickname, $username)");
        });
    }
}
