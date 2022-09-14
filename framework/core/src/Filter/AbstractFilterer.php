<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Filter;

use Flarum\Query\ApplyQueryParametersTrait;
use Flarum\Query\QueryCriteria;
use Flarum\Query\QueryResults;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use InvalidArgumentException;

abstract class AbstractFilterer
{
    use ApplyQueryParametersTrait;

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
     * @param QueryCriteria $criteria
     * @param int|null $limit
     * @param int $offset
     *
     * @return QueryResults
     * @throws InvalidArgumentException
     */
    public function filter(QueryCriteria $criteria, int $limit = null, int $offset = 0): QueryResults
    {
        $actor = $criteria->actor;

        $query = $this->getQuery($actor);

        $filterState = new FilterState($query->getQuery(), $actor);

        foreach ($criteria->query as $filterKey => $filterValue) {
            $negate = false;
            if (substr($filterKey, 0, 1) == '-') {
                $negate = true;
                $filterKey = substr($filterKey, 1);
            }
            foreach (Arr::get($this->filters, $filterKey, []) as $filter) {
                $filterState->addActiveFilter($filter);
                $filter->filter($filterState, $filterValue, $negate);
            }
        }

        $this->applySort($filterState, $criteria->sort, $criteria->sortIsDefault);
        $this->applyOffset($filterState, $offset);
        $this->applyLimit($filterState, $limit + 1);

        foreach ($this->filterMutators as $mutator) {
            $mutator($filterState, $criteria);
        }

        // Execute the filter query and retrieve the results. We get one more
        // results than the user asked for, so that we can say if there are more
        // results. If there are, we will get rid of that extra result.
        $results = $query->get();

        if ($areMoreResults = $limit > 0 && $results->count() > $limit) {
            $results->pop();
        }

        return new QueryResults($results, $areMoreResults);
    }
}
