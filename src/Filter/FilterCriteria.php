<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Filter;

use Flarum\User\User;

/**
 * Represents the criteria that will determine the entire result set of a
 * filter. The limit and offset are not included because they only determine
 * which part of the entire result set will be returned.
 */
class FilterCriteria
{
    /**
     * The user performing the filter.
     *
     * @var User
     */
    public $actor;

    /**
     * The filter query.
     *
     * @var array
     */
    public $queryParams;

    /**
     * An array of sort-order pairs, where the column is the key, and the order
     * is the value. The order may be 'asc', 'desc', or an array of IDs to
     * order by.
     *
     * @var array
     */
    public $sort;

    /**
     * @param User $actor The user performing the filter.
     * @param array $queryParams The filter query.
     * @param array $sort An array of sort-order pairs, where the column is the
     *     key, and the order is the value. The order may be 'asc', 'desc', or
     *     an array of IDs to order by.
     */
    public function __construct(User $actor, $queryParams, array $sort = null)
    {
        $this->actor = $actor;
        $this->queryParams = $queryParams;
        $this->sort = $sort;
    }
}
