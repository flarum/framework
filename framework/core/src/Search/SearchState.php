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
use Flarum\Search\Filter\RefineFilterInterface;
use Flarum\Search\Filter\WindowFilterInterface;
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
     * To decide whether a request is the unfiltered list, such as the All
     * Discussions page, use `getListFilters()` instead.
     *
     * @return FilterInterface[]
     */
    public function getActiveFilters(): array
    {
        return $this->activeFilters;
    }

    /**
     * The active filters that decide which list this is, leaving out those that
     * only pick a window of it (`WindowFilterInterface`) or narrow it
     * (`RefineFilterInterface`). With none, the request is the unfiltered
     * list, such as the All Discussions page.
     *
     * @return FilterInterface[]
     */
    public function getListFilters(): array
    {
        return array_values(array_filter(
            $this->activeFilters,
            fn (FilterInterface $filter) => ! $filter instanceof WindowFilterInterface && ! $filter instanceof RefineFilterInterface
        ));
    }

    public function addActiveFilter(FilterInterface $filter): void
    {
        $this->activeFilters[] = $filter;
    }
}
