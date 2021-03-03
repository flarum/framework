<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Query;

use Flarum\Search\SearchCriteria;
use Flarum\User\User;

/**
 * Represents the criteria that will determine the entire result set of a
 * query. The limit and offset are not included because they only determine
 * which part of the entire result set will be returned.
 */
class QueryCriteria extends SearchCriteria
{
    /**
     * The user performing the query.
     *
     * @var User
     */
    public $actor;

    /**
     * Query params.
     *
     * @var array
     */
    public $query;

    /**
     * An array of sort-order pairs, where the column is the key, and the order
     * is the value. The order may be 'asc', 'desc', or an array of IDs to
     * order by.
     *
     * @var array
     */
    public $sort;

    /**
     * @param User $actor The user performing the query.
     * @param array $query The query params.
     * @param array $sort An array of sort-order pairs, where the column is the
     *     key, and the order is the value. The order may be 'asc', 'desc', or
     *     an array of IDs to order by.
     */
    public function __construct(User $actor, $query, array $sort = null)
    {
        $this->actor = $actor;
        $this->query = $query;
        $this->sort = $sort;
    }
}
