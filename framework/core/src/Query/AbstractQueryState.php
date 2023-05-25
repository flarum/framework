<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Query;

use Closure;
use Flarum\User\User;
use Illuminate\Database\Query\Builder;

abstract class AbstractQueryState
{
    public function __construct(
        protected Builder $query,
        protected User $actor,
        /**
         * An array of sort-order pairs, where the column
         *     is the key, and the order is the value. The order may be 'asc',
         *     'desc', or an array of IDs to order by.
         *     Alternatively, a callable may be used.
         *
         * @var array<string, string|int[]>|Closure $defaultSort
         */
        protected array|Closure $defaultSort = []
    ) {
    }

    /**
     * Get the query builder for the search results query.
     */
    public function getQuery(): Builder
    {
        return $this->query;
    }

    public function getActor(): User
    {
        return $this->actor;
    }

    public function getDefaultSort(): array|Closure
    {
        return $this->defaultSort;
    }

    /**
     * Set the default sort order for the search. This will only be applied if
     * a sort order has not been specified in the search criteria.
     */
    public function setDefaultSort(array|Closure $defaultSort): void
    {
        $this->defaultSort = $defaultSort;
    }
}
