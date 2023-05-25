<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Nicknames;

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Search\GambitInterface;
use Flarum\Search\SearchState;
use Flarum\User\UserRepository;
use Illuminate\Database\Eloquent\Builder;

class NicknameFullTextGambit implements GambitInterface
{
    public function __construct(
        protected UserRepository $users
    ) {}

    private function getUserSearchSubQuery(string $searchValue): Builder
    {
        return $this->users
            ->query()
            ->select('id')
            ->where('username', 'like', "{$searchValue}%")
            ->orWhere('nickname', 'like', "{$searchValue}%");
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
