<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Search;

use Flarum\Search\AbstractFulltextFilter;
use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\SearchState;

/**
 * Audit has no free-text search, but the searcher must declare a fulltext filter
 * so the search manager treats it as a valid full-search target. This is a no-op
 * (it replaces the 1.x NoOpFullTextGambit).
 *
 * @extends AbstractFulltextFilter<DatabaseSearchState>
 */
class FulltextFilter extends AbstractFulltextFilter
{
    public function search(SearchState $state, string $value): void
    {
        // Intentionally empty — audit logs are not full-text searchable.
    }
}
