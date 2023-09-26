<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Search;

use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

abstract class AbstractSearcher
{
    public function __construct(
        /** @var array<string, FilterInterface[]> */
        protected FilterManager $filters,
        /** @var array<callable> */
        protected array $mutators
    ) {
    }

    abstract protected function getQuery(User $actor): Builder;

    public function search(SearchCriteria $criteria, ?int $limit = null, int $offset = 0): SearchResults
    {
        $actor = $criteria->actor;

        $query = $this->getQuery($actor);

        $search = new SearchState($query->getQuery(), $actor, in_array('q', array_keys($criteria->filters), true));

        $this->filters->apply($search, $criteria->filters);

        $this->applySort($search, $criteria->sort, $criteria->sortIsDefault);
        $this->applyOffset($search, $offset);
        $this->applyLimit($search, $limit + 1);

        foreach ($this->mutators as $mutator) {
            $mutator($search, $criteria);
        }

        // Execute the search query and retrieve the results. We get one more
        // results than the user asked for, so that we can say if there are more
        // results. If there are, we will get rid of that extra result.
        $results = $query->get();

        if ($areMoreResults = $limit > 0 && $results->count() > $limit) {
            $results->pop();
        }

        return new SearchResults($results, $areMoreResults);
    }

    protected function applySort(SearchState $query, ?array $sort = null, bool $sortIsDefault = false): void
    {
        if ($sortIsDefault && ! empty($query->getDefaultSort())) {
            $sort = $query->getDefaultSort();
        }

        if (is_callable($sort)) {
            $sort($query->getQuery());
        } else {
            foreach ((array) $sort as $field => $order) {
                if (is_array($order)) {
                    foreach ($order as $value) {
                        $query->getQuery()->orderByRaw(Str::snake($field).' != ?', [$value]);
                    }
                } else {
                    $query->getQuery()->orderBy(Str::snake($field), $order);
                }
            }
        }
    }

    protected function applyOffset(SearchState $query, int $offset): void
    {
        if ($offset > 0) {
            $query->getQuery()->skip($offset);
        }
    }

    protected function applyLimit(SearchState $query, ?int $limit): void
    {
        if ($limit > 0) {
            $query->getQuery()->take($limit);
        }
    }
}
