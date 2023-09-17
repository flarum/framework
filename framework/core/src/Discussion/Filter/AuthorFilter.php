<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Discussion\Filter;

use Flarum\Filter\FilterInterface;
use Flarum\Filter\FilterState;
use Flarum\Filter\ValidateFilterTrait;
use Flarum\User\UserRepository;
use Illuminate\Database\Query\Builder;

class AuthorFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function __construct(
        protected UserRepository $users
    ) {
    }

    public function getFilterKey(): string
    {
        return 'author';
    }

    public function filter(FilterState $filterState, string|array $filterValue, bool $negate): void
    {
        $this->constrain($filterState->getQuery(), $filterValue, $negate);
    }

    protected function constrain(Builder $query, string|array $rawUsernames, bool $negate): void
    {
        $usernames = $this->asStringArray($rawUsernames);

        $ids = $this->users->getIdsForUsernames($usernames);

        $query->whereIn('discussions.user_id', $ids, 'and', $negate);
    }
}
