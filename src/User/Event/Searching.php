<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\Event;

use Flarum\Search\SearchCriteria;
use Flarum\User\Search\UserSearch;

class Searching
{
    /**
     * @var \Flarum\User\Search\UserSearch
     */
    public $search;

    /**
     * @var SearchCriteria
     */
    public $criteria;

    /**
     * @param UserSearch $search
     * @param SearchCriteria $criteria
     */
    public function __construct(UserSearch $search, SearchCriteria $criteria)
    {
        $this->search = $search;
        $this->criteria = $criteria;
    }
}
