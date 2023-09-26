<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Discussion\Search\Filter;

use Flarum\Search\FilterInterface;
use Flarum\Search\SearchState;
use Flarum\Search\ValidateFilterTrait;
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

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        $this->constrain($state->getQuery(), $value, $negate);
    }

    protected function constrain(Builder $query, string|array $rawUsernames, bool $negate): void
    {
        $usernames = $this->asStringArray($rawUsernames);

        $ids = $this->users->getIdsForUsernames($usernames);

        $query->whereIn('discussions.user_id', $ids, 'and', $negate);
    }
}
