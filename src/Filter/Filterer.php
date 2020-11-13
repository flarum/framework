<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Filter;

use Flarum\Search\ApplySearchParametersTrait;
use Flarum\Search\SearchResults;
use Illuminate\Support\Arr;

class Filterer
{
    use ApplySearchParametersTrait;

    protected static $filters = [];

    protected static $filterMutators = [];

    public static function addFilter($resource, FilterInterface $filter)
    {
        if (!array_key_exists($resource, static::$filters)) {
            static::$filters[$resource] = [];
        }

        if (!array_key_exists($filter->getKey(), static::$filters[$resource])) {
            static::$filters[$resource][$filter->getKey()] = [];
        }

        static::$filters[$resource][$filter->getKey()][] = $filter;
    }

    public static function addFilterMutator($resource, $mutator)
    {
        if (!array_key_exists($resource, static::$filterMutators)) {
            static::$filterMutators[$resource] = [];
        }

        static::$filterMutators[$resource][] = $mutator;
    }

    /**
     * @param FilterCriteria $criteria
     * @param int|null $limit
     * @param int $offset
     *
     * @return FilterResults
     */
    public function filter($actor, $query, $filters, $sort = null, $limit = null, $offset = 0, array $load = [])
    {
        $resource = get_class($query->getModel());

        $query->whereVisibleTo($actor);

        $wrappedFilter = new WrappedFilter($query->getQuery(), $actor);

        foreach ($filters as $filterKey => $filterValue) {
            foreach (Arr::get(static::$filters, "$resource.$filterKey", []) as $filter) {
                $filter->apply($wrappedFilter, $filterValue);
            }
        }

        $this->applySort($wrappedFilter, $sort);
        $this->applyOffset($wrappedFilter, $offset);
        $this->applyLimit($wrappedFilter, $limit + 1);

        foreach (Arr::get(static::$filterMutators, $resource, []) as $mutator) {
            $mutator($query, $actor, $filters, $sort);
        }

        // Execute the filter query and retrieve the results. We get one more
        // results than the user asked for, so that we can say if there are more
        // results. If there are, we will get rid of that extra result.
        $results = $query->get();

        if ($areMoreResults = $limit > 0 && $results->count() > $limit) {
            $results->pop();
        }

        $results->load($load);

        return new SearchResults($results, $areMoreResults);
    }
}
