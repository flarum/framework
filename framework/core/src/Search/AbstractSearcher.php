<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Search;

use Flarum\Query\ApplyQueryParametersTrait;
use Flarum\Query\QueryCriteria;
use Flarum\Query\QueryResults;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

abstract class AbstractSearcher
{
    use ApplyQueryParametersTrait;

    public function __construct(
        /** @var array<string, FilterInterface[]> */
        protected FilterManager $filters,
        /** @var array<callable> */
        protected array $mutators
    ) {
    }

    abstract protected function getQuery(User $actor): Builder;

    public function search(QueryCriteria $criteria, ?int $limit = null, int $offset = 0): QueryResults
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

        return new QueryResults($results, $areMoreResults);
    }
}
