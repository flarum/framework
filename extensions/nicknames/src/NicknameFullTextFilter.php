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
            ->where('username', 'like', "{$searchValue}%")
            ->orWhere('nickname', 'like', "{$searchValue}%");
    }

    public function search(SearchState $state, string $value): void
    {
        $state->getQuery()
            ->whereIn(
                'id',
                $this->getUserSearchSubQuery($value)
            );
    }
}
