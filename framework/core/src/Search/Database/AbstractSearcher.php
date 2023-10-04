<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Search\Database;

use Flarum\Search\Filter\FilterManager;
use Flarum\Search\IndexerInterface;
use Flarum\Search\SearchCriteria;
use Flarum\Search\SearcherInterface;
use Flarum\Search\SearchResults;
use Flarum\User\User;
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

        $search = new DatabaseSearchState($actor, in_array('q', array_keys($criteria->filters), true));
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

        return new SearchResults($results, $areMoreResults);
    }

    protected function applySort(DatabaseSearchState $query, ?array $sort = null, bool $sortIsDefault = false): void
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

    protected function applyOffset(DatabaseSearchState $query, int $offset): void
    {
        if ($offset > 0) {
            $query->getQuery()->skip($offset);
        }
    }

    protected function applyLimit(DatabaseSearchState $query, ?int $limit): void
    {
        if ($limit > 0) {
            $query->getQuery()->take($limit);
        }
    }
}
