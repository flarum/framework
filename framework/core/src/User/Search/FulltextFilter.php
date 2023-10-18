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
     * @return Builder<User>
     */
    private function getUserSearchSubQuery(string $searchValue): Builder
    {
        return $this->users
            ->query()
            ->select('id')
            ->where('username', 'like', "$searchValue%");
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
