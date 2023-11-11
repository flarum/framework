<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Search\Database;

use Closure;
use Flarum\Search\Filter\FilterManager;
use Flarum\Search\SearchCriteria;
use Flarum\Search\SearcherInterface;
use Flarum\Search\SearchResults;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

abstract class AbstractSearcher implements SearcherInterface
{
    public function __construct(
        protected FilterManager $filters,
        /** @var array<callable> */
        protected array $mutators
    ) {
    }

    public function search(SearchCriteria $criteria): SearchResults
    {
        $actor = $criteria->actor;

        $query = $this->getQuery($actor);

        $search = new DatabaseSearchState($actor, $criteria->isFulltext());
        $search->setQuery($query->getQuery());

        $this->filters->apply($search, $criteria->filters);

        $this->applySort($search, $criteria->sort, $criteria->sortIsDefault);
        $this->applyOffset($search, $criteria->offset);
        $this->applyLimit($search, $criteria->limit + 1);

        foreach ($this->mutators as $mutator) {
            $mutator($search, $criteria);
        }

        // Execute the search query and retrieve the results. We get one more
        // results than the user asked for, so that we can say if there are more
        // results. If there are, we will get rid of that extra result.
        $results = $query->get();

        if ($areMoreResults = $criteria->limit > 0 && $results->count() > $criteria->limit) {
            $results->pop();
        }

        return new SearchResults($results, $areMoreResults, $this->getTotalResults($query));
    }

    protected function getTotalResults(Builder $query): Closure
    {
        return function () use ($query) {
            $query = $query->toBase();

            if ($query->unions) {
                $query->unions = null; // @phpstan-ignore-line
                $query->unionLimit = null; // @phpstan-ignore-line
                $query->unionOffset = null; // @phpstan-ignore-line
                $query->unionOrders = null; // @phpstan-ignore-line
                $query->setBindings([], 'union');
            }

            $query->offset = null; // @phpstan-ignore-line
            $query->limit = null; // @phpstan-ignore-line
            $query->orders = null; // @phpstan-ignore-line
            $query->setBindings([], 'order');

            return $query->getConnection()
                ->table($query, 'results')
                ->count();
        };
    }

    protected function applySort(DatabaseSearchState $state, ?array $sort = null, bool $sortIsDefault = false): void
    {
        if ($sortIsDefault && ! empty($state->getDefaultSort())) {
            $sort = $state->getDefaultSort();
        }

        if (is_callable($sort)) {
            $sort($state->getQuery());
        } else {
            foreach ((array) $sort as $field => $order) {
                if (is_array($order)) {
                    foreach ($order as $value) {
                        $state->getQuery()->orderByRaw(Str::snake($field).' != ?', [$value]);
                    }
                } else {
                    $state->getQuery()->orderBy(Str::snake($field), $order);
                }
            }
        }
    }

    protected function applyOffset(DatabaseSearchState $state, int $offset): void
    {
        if ($offset > 0) {
            $state->getQuery()->skip($offset);
        }
    }

    protected function applyLimit(DatabaseSearchState $state, ?int $limit): void
    {
        if ($limit > 0) {
            $state->getQuery()->take($limit);
        }
    }
}
