<?php namespace Flarum\Events;

use Flarum\Core\Discussions\Search\DiscussionSearch;
use Flarum\Core\Search\SearchCriteria;

class DiscussionSearchWillBePerformed
{
    /**
     * @var DiscussionSearch
     */
    public $search;

    /**
     * @var SearchCriteria
     */
    public $criteria;

    /**
     * @param DiscussionSearch $search
     * @param SearchCriteria $criteria
     */
    public function __construct(DiscussionSearch $search, SearchCriteria $criteria)
    {
        $this->search = $search;
        $this->criteria = $criteria;
    }
}
