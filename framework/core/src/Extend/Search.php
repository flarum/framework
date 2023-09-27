<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Flarum\Extension\Extension;
use Flarum\Search\AbstractDriver;
use Flarum\Search\AbstractFulltextFilter;
use Flarum\Search\Database\AbstractSearcher;
use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchCriteria;
use Illuminate\Contracts\Container\Container;

class Search implements ExtenderInterface
{
    private array $searchers = [];
    private array $fulltext = [];
    private array $filters = [];
    private array $mutators = [];

    /**
     * @param class-string<AbstractDriver> $driverClass: The driver class you are modifying or adding.
     *                               This driver must extend \Flarum\Search\AbstractDriver.
     */
    public function __construct(
        private readonly string $driverClass
    ) {
    }

    /**
     * Add a filter to this searcher. Filters are used to filter search queries.
     *
     * @param class-string<FilterInterface> $modelClass : The class of the model subject to searching/filtering.
     *                              This model must extend \Flarum\Database\AbstractModel.
     * @param class-string<AbstractSearcher> $searcherClass : The class of the Searcher for this model
     *                                This searcher must implement \Flarum\Search\SearcherInterface.
     *                                Or extend \Flarum\Search\Database\AbstractSearcher if using the default driver.
     * @return self
     */
    public function addSearcher(string $modelClass, string $searcherClass): self
    {
        $this->searchers[$modelClass] = $searcherClass;

        return $this;
    }

    /**
     * Add a filter to this searcher. Filters are used to filter search queries.
     *
     * @param class-string<AbstractSearcher> $searcherClass : The class of the Searcher for this model
     *                                This searcher must implement \Flarum\Search\SearcherInterface.
     *                                Or extend \Flarum\Search\Database\AbstractSearcher if using the default driver.
     * @param class-string<FilterInterface> $filterClass: The ::class attribute of the filter you are adding.
     *                             This filter must implement \Flarum\Search\FilterInterface
     * @return self
     */
    public function addFilter(string $searcherClass, string $filterClass): self
    {
        $this->filters[$searcherClass][] = $filterClass;

        return $this;
    }

    /**
     * Set the full text filter for this searcher. The full text filter actually executes the search.
     *
     * @param class-string<AbstractSearcher> $searcherClass : The class of the Searcher for this model
     *                                This searcher must implement \Flarum\Search\SearcherInterface.
     *                                Or extend \Flarum\Search\Database\AbstractSearcher if using the default driver.
     * @param class-string<AbstractFulltextFilter> $fulltextClass: The ::class attribute of the full test filter you are adding.
     *                             This filter must implement \Flarum\Search\FilterInterface
     * @return self
     */
    public function setFulltext(string $searcherClass, string $fulltextClass): self
    {
        $this->fulltext[$searcherClass] = $fulltextClass;

        return $this;
    }

    /**
     * Add a callback through which to run all search queries after filters have been applied.
     *
     * @param class-string<AbstractSearcher> $searcherClass : The class of the Searcher for this model
     *                                This searcher must implement \Flarum\Search\SearcherInterface.
     *                                Or extend \Flarum\Search\Database\AbstractSearcher if using the default driver.
     * @param (callable(DatabaseSearchState $search, SearchCriteria $criteria): void)|class-string $callback
     *
     * The callback can be a closure or an invokable class, and should accept:
     * - \Flarum\Search\SearchState $search
     * - \Flarum\Query\QueryCriteria $criteria
     *
     * The callback should return void.
     *
     * @return self
     */
    public function addMutator(string $searcherClass, callable|string $callback): self
    {
        $this->mutators[$searcherClass][] = $callback;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null): void
    {
        $container->extend('flarum.search.drivers', function (array $oldDrivers) {
            $oldDrivers[$this->driverClass] = array_merge(
                $oldDrivers[$this->driverClass] ?? [],
                $this->searchers
            );

            return $oldDrivers;
        });

        $container->extend('flarum.search.fulltext', function (array $oldFulltextFilters) {
            foreach ($this->fulltext as $searcherClass => $fulltextClass) {
                $oldFulltextFilters[$searcherClass] = $fulltextClass;
            }

            return $oldFulltextFilters;
        });

        $container->extend('flarum.search.filters', function (array $oldFilters) {
            foreach ($this->filters as $searcherClass => $filters) {
                $oldFilters[$searcherClass] = array_merge(
                    $oldFilters[$searcherClass] ?? [],
                    $filters
                );
            }

            return $oldFilters;
        });

        $container->extend('flarum.search.mutators', function (array $oldMutators) {
            foreach ($this->mutators as $searcherClass => $mutators) {
                $oldMutators[$searcherClass] = array_merge(
                    $oldMutators[$searcherClass] ?? [],
                    $mutators
                );
            }

            return $oldMutators;
        });
    }
}
