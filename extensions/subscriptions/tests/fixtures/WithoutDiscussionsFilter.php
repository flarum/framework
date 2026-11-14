<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Subscriptions\Tests\fixtures;

use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\RefineFilterInterface;
use Flarum\Search\SearchState;

/**
 * A refinement a person picks on a list, such as a date range: here it leaves
 * out the discussions whose IDs it is given.
 *
 * @implements RefineFilterInterface<DatabaseSearchState>
 */
class WithoutDiscussionsFilter implements RefineFilterInterface
{
    public function getFilterKey(): string
    {
        return 'without';
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        $state->getQuery()->whereNotIn('discussions.id', explode(',', (string) $value));
    }
}
