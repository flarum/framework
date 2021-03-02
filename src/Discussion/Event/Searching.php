<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Discussion\Event;

use Flarum\Search\SearchCriteria;
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
     * @var \Flarum\Search\SearchCriteria
     */
    public $criteria;

    /**
     * @param SearchState $search
     * @param \Flarum\Search\SearchCriteria $criteria
     */
    public function __construct(SearchState $search, SearchCriteria $criteria)
    {
        $this->search = $search;
        $this->criteria = $criteria;
    }
}
