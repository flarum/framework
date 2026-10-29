<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\Search;

use Flarum\Search\AbstractFulltextFilter;
use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\SearchState;
use Flarum\User\User;
use Flarum\User\UserRepository;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends AbstractFulltextFilter<DatabaseSearchState>
 */
class FulltextFilter extends AbstractFulltextFilter
{
    public function __construct(
        protected UserRepository $users
    ) {
    }

    /**
     * Matches the text anywhere in a username, so part of a name finds them,
     * with usernames that start with it first.
     */
    public function search(SearchState $state, string $value): void
    {
        $state->getQuery()
            ->whereIn(
                'id',
                match ($state->getQuery()->getConnection()->getDriverName()) {
                    'pgsql' => $this->getUserSearchSubQuery("%$value%", 'ilike'),
                    'sqlite' => $this->getUserSearchSubQueryLower('%'.strtolower($value).'%'),
                    default => $this->getUserSearchSubQuery("%$value%", 'like'),
                }
            );

        $state->setDefaultSort(function (Builder $query) use ($value) {
            $username = $query->getGrammar()->wrap('users.username');

            $query
                ->orderByRaw("CASE WHEN LOWER($username) LIKE ? THEN 0 ELSE 1 END", [mb_strtolower($value).'%'])
                ->orderBy('users.username');
        });
    }

    /**
     * @return Builder<User>
     */
    private function getUserSearchSubQuery(string $pattern, string $operator): Builder
    {
        return $this->users
            ->query()
            ->select('id')
            ->where('username', $operator, $pattern);
    }

    /**
     * @return Builder<User>
     */
    private function getUserSearchSubQueryLower(string $pattern): Builder
    {
        return $this->users
            ->query()
            ->select('id')
            ->whereRaw('LOWER(username) LIKE ?', [$pattern]);
    }
}
