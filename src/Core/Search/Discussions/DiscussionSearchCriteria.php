<?php namespace Flarum\Core\Search\Discussions;

class DiscussionSearchCriteria
{
    public $user;

    public $query;

    public $sort;

    public function __construct($user, $query, $sort)
    {
        $this->user = $user;
        $this->query = $query;
        $this->sort = $sort;
    }
}
