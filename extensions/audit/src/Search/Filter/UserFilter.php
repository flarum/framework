<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Search\Filter;

use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;
use Flarum\Search\ValidateFilterTrait;
use Flarum\User\UserRepository;

/**
 * @implements FilterInterface<DatabaseSearchState>
 */
class UserFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function __construct(
        protected UserRepository $users
    ) {
    }

    public function getFilterKey(): string
    {
        return 'user';
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        $ids = array_values($this->users->getIdsForUsernames($this->asStringArray($value)));

        $query = $state->getQuery();

        if ($negate) {
            // Keep entries about no user: NULL NOT IN (...) is never true.
            $query->where(fn ($query) => $query->whereNull('payload->user_id')->orWhereNotIn('payload->user_id', $ids));
        } else {
            $query->whereIn('payload->user_id', $ids);
        }
    }
}
