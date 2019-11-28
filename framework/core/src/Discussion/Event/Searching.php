<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Discussion\Event;

use Flarum\Discussion\Search\DiscussionSearch;
use Flarum\Search\SearchCriteria;

class Searching
{
    /**
     * @var DiscussionSearch
     */
    public $search;

    /**
     * @var \Flarum\Search\SearchCriteria
     */
    public $criteria;

    /**
     * @param DiscussionSearch $search
     * @param \Flarum\Search\SearchCriteria $criteria
     */
    public function __construct(DiscussionSearch $search, SearchCriteria $criteria)
    {
        $this->search = $search;
        $this->criteria = $criteria;
    }
}
