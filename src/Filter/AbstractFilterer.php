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
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use InvalidArgumentException;

abstract class AbstractFilterer
{
    use ApplySearchParametersTrait;

    protected $filters;

    protected $filterMutators;

    /**
     * @param array $filters
     * @param array $filterMutators
     */
    public function __construct(array $filters, array $filterMutators)
    {
        $this->filters = $filters;
        $this->filterMutators = $filterMutators;
    }

    abstract protected function getQuery(User $actor): Builder;

    /**
     * @param FilterCriteria $criteria
     * @param mixed|null $limit
     * @param int $offset
     * @param array $load
     * @return SearchResults
     * @throws InvalidArgumentException
     */
    public function filter(FilterCriteria $criteria, int $limit = null, int $offset = 0, array $load = []): SearchResults
    {
        $actor = $criteria->actor;

        $query = $this->getQuery($actor);

        $wrappedFilter = new WrappedFilter($query->getQuery(), $actor);

        foreach ($criteria->queryParams as $filterKey => $filterValue) {
            $negate = false;
            if (substr($filterKey, 0, 1) == '-') {
                $negate = true;
                $filterKey = substr($filterKey, 1);
            }
            foreach (Arr::get($this->filters, $filterKey, []) as $filter) {
                $filter->filter($wrappedFilter, $filterValue, $negate);
            }
        }

        $this->applySort($wrappedFilter, $criteria->sort);
        $this->applyOffset($wrappedFilter, $offset);
        $this->applyLimit($wrappedFilter, $limit + 1);

        foreach ($this->filterMutators as $mutator) {
            $mutator($query, $actor, $criteria->queryParams, $criteria->sort);
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
