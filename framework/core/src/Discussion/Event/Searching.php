<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Discussion\Event;

use Flarum\Query\QueryCriteria;
use Flarum\Search\SearchState;

/**
 * @deprecated beta 16, remove beta 17
 */
class Searching
{
    /**
     * @var SearchState
     */
    public $search;

    /**
     * @var \Flarum\Query\QueryCriteria
     */
    public $criteria;

    /**
     * @param SearchState $search
     * @param \Flarum\Query\QueryCriteria $criteria
     */
    public function __construct(SearchState $search, QueryCriteria $criteria)
    {
        $this->search = $search;
        $this->criteria = $criteria;
    }
}
