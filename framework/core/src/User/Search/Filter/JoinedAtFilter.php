<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\Search\Filter;

use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\DescribesFilter;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\Filter\FiltersByRange;
use Flarum\Search\SearchState;
use Flarum\User\User;

/**
 * Filters users by the day they joined: `filter[joinedAt]=2024-01-01..2024-06-30`,
 * either side may be empty.
 *
 * @implements FilterInterface<DatabaseSearchState>
 */
class JoinedAtFilter implements FilterInterface, DescribesFilter
{
    use FiltersByRange;

    public function getFilterKey(): string
    {
        return 'joinedAt';
    }

    /**
     * Ungated, like `filter()`: the join time is visible to everyone who can list users.
     */
    public function describe(User $actor): ?array
    {
        return $this->describeRange();
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        $this->constrainRange($state->getQuery(), 'joined_at', $value, $negate);
    }
}
