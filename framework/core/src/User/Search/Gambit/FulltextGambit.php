<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\Search\Gambit;

use Flarum\Search\GambitInterface;
use Flarum\Search\SearchState;
use Flarum\User\User;
use Flarum\User\UserRepository;
use Illuminate\Database\Eloquent\Builder;

class FulltextGambit implements GambitInterface
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

    public function apply(SearchState $search, string $bit): bool
    {
        $search->getQuery()
            ->whereIn(
                'id',
                $this->getUserSearchSubQuery($bit)
            );

        return true;
    }
}
