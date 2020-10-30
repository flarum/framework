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
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

abstract class AbstractSearcher
{
    protected static $gambitManagers = [];

    protected static $searchMutators = [];

    public static function addSearchMutator($searcherClass, $mutator)
    {
        if (! array_key_exists($searcherClass, static::$searchMutators)) {
            static::$searchMutators[$searcherClass] = [];
        }

        static::$searchMutators[$searcherClass][] = $mutator;
    }

    public static function gambitManager($searcher)
    {
        if (! array_key_exists($searcher, static::$gambitManagers)) {
            static::$gambitManagers[$searcher] = new GambitManager;
        }

        return static::$gambitManagers[$searcher];
    }

    abstract protected function getQuery(User $actor): Builder;

    abstract protected function getSearch(Builder $query, User $actor): AbstractSearch;

    protected function mutateSearch(AbstractSearch $search, SearchCriteria $criteria)
    {
        foreach (Arr::get(static::$searchMutators, static::class, []) as $mutator) {
            $mutator($search, $criteria);
        }
    }

    /**
     * @param SearchCriteria $criteria
     * @param int|null $limit
     * @param int $offset
     *
     * @return SearchResults
     */
    public function search(SearchCriteria $criteria, $limit = null, $offset = 0, array $load = [])
    {
        $actor = $criteria->actor;

        $query = $this->getQuery($actor);

        $search = $this->getSearch($query, $actor);

        $this->gambitManager(static::class)->apply($search, $criteria->query);
        $this->applySort($search, $criteria->sort);
        $this->applyOffset($search, $offset);
        $this->applyLimit($search, $limit + 1);

        $this->mutateSearch($search, $criteria);

        // Execute the search query and retrieve the results. We get one more
        // results than the user asked for, so that we can say if there are more
        // results. If there are, we will get rid of that extra result.
        $results = $query->get();

        if ($areMoreResults = $limit > 0 && $results->count() > $limit) {
            $results->pop();
        }

        $results->load($load);

        return new SearchResults($results, $areMoreResults);
    }

    /**
     * Apply sort criteria to a discussion search.
     *
     * @param AbstractSearch $search
     * @param array $sort
     */
    protected function applySort(AbstractSearch $search, array $sort = null)
    {
        $sort = $sort ?: $search->getDefaultSort();

        if (is_callable($sort)) {
            $sort($search->getQuery());
        } else {
            foreach ($sort as $field => $order) {
                if (is_array($order)) {
                    foreach ($order as $value) {
                        $search->getQuery()->orderByRaw(Str::snake($field).' != ?', [$value]);
                    }
                } else {
                    $search->getQuery()->orderBy(Str::snake($field), $order);
                }
            }
        }
    }

    /**
     * @param AbstractSearch $search
     * @param int $offset
     */
    protected function applyOffset(AbstractSearch $search, $offset)
    {
        if ($offset > 0) {
            $search->getQuery()->skip($offset);
        }
    }

    /**
     * @param AbstractSearch $search
     * @param int|null $limit
     */
    protected function applyLimit(AbstractSearch $search, $limit)
    {
        if ($limit > 0) {
            $search->getQuery()->take($limit);
        }
    }
}
