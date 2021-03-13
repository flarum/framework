<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\Event;

use Flarum\Query\QueryCriteria;
use Flarum\Search\SearchState;

/**
 * @deprecated beta 16, remove beta 17
 */
class Searching
{
    /**
     * @var \Flarum\User\Search\SearchState
     */
    public $search;

    /**
     * @var QueryCriteria
     */
    public $criteria;

    /**
     * @param SearchState $search
     * @param QueryCriteria $criteria
     */
    public function __construct(SearchState $search, QueryCriteria $criteria)
    {
        $this->search = $search;
        $this->criteria = $criteria;
    }
}
