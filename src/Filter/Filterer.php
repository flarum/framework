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

class Filterer
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

    /**
     * @param User $actor
     * @param Builder $query
     * @param array $filters
     * @param mixed|null $sort
     * @param mixed|null $limit
     * @param int $offset
     * @param array $load
     * @return SearchResults
     * @throws InvalidArgumentException
     */
    public function filter(User $actor, Builder $query, array $filters, array $sort = null, int $limit = null, int $offset = 0, array $load = []): SearchResults
    {
        $resource = get_class($query->getModel());

        $query->whereVisibleTo($actor);

        $wrappedFilter = new WrappedFilter($query->getQuery(), $actor);

        foreach ($filters as $filterKey => $filterValue) {
            $negate = false;
            if (substr($filterKey, 0, 1) == '-') {
                $negate = true;
                $filterKey = substr($filterKey, 1);
            }
            foreach (Arr::get($this->filters, "$resource.$filterKey", []) as $filter) {
                $filter->filter($wrappedFilter, $filterValue, $negate);
            }
        }

        $this->applySort($wrappedFilter, $sort);
        $this->applyOffset($wrappedFilter, $offset);
        $this->applyLimit($wrappedFilter, $limit + 1);

        foreach (Arr::get($this->filterMutators, $resource, []) as $mutator) {
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
