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
        $search->setQuery($query);

        $this->filters->apply($search, $criteria->filters);

        $this->applySort($search, $criteria->sort, $criteria->sortIsDefault);
        $this->applyOffset($search, $criteria->offset);
        $this->applyLimit($search, $criteria->limit ? $criteria->limit + 1 : null);

        foreach ($this->mutators as $mutator) {
            $mutator($search, $criteria);
        }

        // After the mutators, since one may have rewritten the query into a union.
        $this->applyTieBreaker($search);

        $query = $search->getQuery();

        // Execute the search query and retrieve the results. We get one more
        // results than the user asked for, so that we can say if there are more
        // results. If there are, we will get rid of that extra result.
        $results = $query->get();

        if ($areMoreResults = ($criteria->limit > 0 && $results->count() > $criteria->limit)) {
            $results->pop();
        }

        return new SearchResults($results, $areMoreResults, $this->getTotalResults($query->clone()));
    }

    protected function getTotalResults(Builder $query): Closure
    {
        return function () use ($query) {
            $query = $query->toBase();

            if ($query->unions) {
                $query = $query
                    ->cloneWithout(['unions', 'unionLimit', 'unionOffset', 'unionOrders'])
                    ->cloneWithoutBindings(['union']);
            }

            return $query->getCountForPagination();
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

    /**
     * Order rows the requested sort leaves tied.
     *
     * Sorting by a column that isn't unique leaves the order of tied rows to the database,
     * which is free to differ between engines, versions and even executions. Results are
     * then paginated by offset, so an unstable order can repeat a row on one page and skip
     * it on the next. Ending every sort on the primary key makes the order total.
     */
    protected function applyTieBreaker(DatabaseSearchState $state): void
    {
        $query = $state->getQuery();
        $base = $query->getQuery();
        $model = $query->getModel();

        if (! empty($base->unions)) {
            // A union's ordering resolves against the columns of the result, so it cannot
            // name a table.
            $base->unionOrders[] = [
                'column' => $model->getKeyName(),
                'direction' => static::lastOrderDirection($base->unionOrders),
            ];

            return;
        }

        $query->orderBy(
            $model->getQualifiedKeyName(),
            static::lastOrderDirection($base->orders)
        );
    }

    /**
     * The direction of the last ordering in a list, or ascending if it has none.
     *
     * The tie breaker follows it rather than always ascending: an InnoDB secondary index
     * already carries the primary key, so `column DESC, id DESC` is still answered by an
     * index on `column` alone, where mixing the two directions forces a filesort.
     */
    protected static function lastOrderDirection(?array $orders): string
    {
        foreach (array_reverse($orders ?? []) as $order) {
            if (isset($order['direction'])) {
                return $order['direction'];
            }
        }

        return 'asc';
    }

    protected function applyOffset(DatabaseSearchState $state, int $offset): void
    {
        if ($offset > 0) {
            $state->getQuery()->skip($offset);
        }
    }

    protected function applyLimit(DatabaseSearchState $state, ?int $limit): void
    {
        if ($limit && $limit > 0) {
            $state->getQuery()->take($limit);
        }
    }
}
