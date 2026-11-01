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
 * Filters users by the day they were last seen: `filter[lastSeenAt]=..2024-01-01`.
 *
 * Users may hide their last seen time (`discloseOnline`), so, like the
 * `lastSeenAt` sort, the filter only applies for actors with the
 * `user.viewLastSeenAt` permission, who can see everyone's.
 *
 * @implements FilterInterface<DatabaseSearchState>
 */
class LastSeenAtFilter implements FilterInterface, DescribesFilter
{
    use FiltersByRange;

    public function getFilterKey(): string
    {
        return 'lastSeenAt';
    }

    public function describe(User $actor): ?array
    {
        // Mirrors `filter()`, which ignores the value without this permission.
        if (! $actor->hasPermission('user.viewLastSeenAt')) {
            return null;
        }

        return $this->describeRange();
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        if (! $state->getActor()->hasPermission('user.viewLastSeenAt')) {
            return;
        }

        $this->constrainRange($state->getQuery(), 'last_seen_at', $value, $negate);
    }
}
