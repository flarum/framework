<?php namespace Flarum\Events;

use Flarum\Core\Users\Search\UserSearch;
use Flarum\Core\Search\SearchCriteria;

class UserSearchWillBePerformed
{
    /**
     * @var UserSearch
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
