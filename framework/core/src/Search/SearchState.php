<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Search;

use Flarum\Query\AbstractQueryState;

class SearchState extends AbstractQueryState
{
    /**
     * @var FilterInterface[]
     */
    protected array $activeFilters = [];

    /**
     * Get a list of the filters that are active.
     *
     * @return FilterInterface[]
     */
    public function getActiveFilters(): array
    {
        return $this->activeFilters;
    }

    public function addActiveFilter(FilterInterface $filter): void
    {
        $this->activeFilters[] = $filter;
    }
}
