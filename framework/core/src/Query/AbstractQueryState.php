<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Query;

use Flarum\User\User;
use Illuminate\Database\Query\Builder;

abstract class AbstractQueryState
{
    /**
     * @var Builder
     */
    protected $query;

    /**
     * @var User
     */
    protected $actor;

    /**
     * @var mixed
     */
    protected $defaultSort = [];

    /**
     * @param Builder $query
     * @param User $actor
     */
    public function __construct(Builder $query, User $actor, $defaultSort = [])
    {
        $this->query = $query;
        $this->actor = $actor;
        $this->defaultSort = $defaultSort;
    }

    /**
     * Get the query builder for the search results query.
     *
     * @return Builder
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Get the user who is performing the search.
     *
     * @return User
     */
    public function getActor()
    {
        return $this->actor;
    }

    /**
     * Get the default sort order for the search.
     *
     * @return array
     */
    public function getDefaultSort()
    {
        return $this->defaultSort;
    }

    /**
     * Set the default sort order for the search. This will only be applied if
     * a sort order has not been specified in the search criteria.
     *
     * @param mixed $defaultSort An array of sort-order pairs, where the column
     *     is the key, and the order is the value. The order may be 'asc',
     *     'desc', or an array of IDs to order by.
     *     Alternatively, a callable may be used.
     * @return mixed
     */
    public function setDefaultSort($defaultSort)
    {
        $this->defaultSort = $defaultSort;
    }
}
