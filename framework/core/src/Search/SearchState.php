<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Search;

use Closure;
use Flarum\Search\Filter\FilterInterface;
use Flarum\User\User;

class SearchState
{
    /**
     * @var FilterInterface[]
     */
    protected array $activeFilters = [];

    final public function __construct(
        protected User $actor,
        /**
         * Whether this is a fulltext search or just filtering.
         */
        protected bool $fulltextSearch,
        /**
         * An array of sort-order pairs, where the column
         *     is the key, and the order is the value. The order may be 'asc',
         *     'desc', or an array of IDs to order by.
         *     Alternatively, a callable may be used.
         *
         * @var array<string, string|int[]>|Closure $defaultSort
         */
        protected array|Closure $defaultSort = []
    ) {
    }

    public function getActor(): User
    {
        return $this->actor;
    }

    public function getDefaultSort(): array|Closure
    {
        return $this->defaultSort;
    }

    /**
     * Set the default sort order for the search. This will only be applied if
     * a sort order has not been specified in the search criteria.
     */
    public function setDefaultSort(array|Closure $defaultSort): void
    {
        $this->defaultSort = $defaultSort;
    }

    public function isFulltextSearch(): bool
    {
        return $this->fulltextSearch;
    }

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
