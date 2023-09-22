<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Flarum\Extension\Extension;
use Flarum\Query\QueryCriteria;
use Flarum\Search\AbstractFulltextFilter;
use Flarum\Search\AbstractSearcher;
use Flarum\Search\FilterInterface;
use Flarum\Search\SearchState;
use Illuminate\Contracts\Container\Container;

class SimpleFlarumSearch implements ExtenderInterface
{
    private ?string $fullTextFilter = null;
    private array $filters = [];
    private array $searchMutators = [];

    /**
     * @param class-string<AbstractSearcher> $searcher: The ::class attribute of the Searcher you are modifying.
     *                               This searcher must extend \Flarum\Search\AbstractSearcher.
     */
    public function __construct(
        private readonly string $searcher
    ) {
    }

    /**
     * Add a filter to this searcher. Filters are used to filter search queries.
     *
     * @param class-string<FilterInterface> $filterClass: The ::class attribute of the filter you are adding.
     *                             This filter must implement \Flarum\Search\FilterInterface
     * @return self
     */
    public function addFilter(string $filterClass): self
    {
        $this->filters[] = $filterClass;

        return $this;
    }

    /**
     * Set the full text filter for this searcher. The full text filter actually executes the search.
     *
     * @param class-string<AbstractFulltextFilter> $fulltextClass: The ::class attribute of the full test filter you are adding.
     *                             This filter must implement \Flarum\Search\FilterInterface
     * @return self
     */
    public function setFullTextFilter(string $fulltextClass): self
    {
        $this->fullTextFilter = $fulltextClass;

        return $this;
    }

    /**
     * Add a callback through which to run all search queries after filters have been applied.
     *
     * @param (callable(SearchState $search, QueryCriteria $criteria): void)|class-string $callback
     *
     * The callback can be a closure or an invokable class, and should accept:
     * - \Flarum\Search\SearchState $search
     * - \Flarum\Query\QueryCriteria $criteria
     *
     * The callback should return void.
     *
     * @return self
     */
    public function addSearchMutator(callable|string $callback): self
    {
        $this->searchMutators[] = $callback;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null): void
    {
        if (! is_null($this->fullTextFilter)) {
            $container->extend('flarum.simple_search.fulltext_filters', function (array $oldFulltextFilters) {
                $oldFulltextFilters[$this->searcher] = $this->fullTextFilter;

                return $oldFulltextFilters;
            });
        }

        $container->extend('flarum.simple_search.filters', function (array $oldFilters) {
            // We need the key to be set, even if there are no filters, so that the searcher is registered.
            $oldFilters[$this->searcher] = $oldFilters[$this->searcher] ?? [];

            foreach ($this->filters as $filter) {
                $oldFilters[$this->searcher][] = $filter;
            }

            return $oldFilters;
        });

        $container->extend('flarum.simple_search.search_mutators', function (array $oldMutators) {
            foreach ($this->searchMutators as $mutator) {
                $oldMutators[$this->searcher][] = $mutator;
            }

            return $oldMutators;
        });
    }
}
