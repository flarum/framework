<?php namespace Flarum\Core\Search\Discussions;

class DiscussionSearchCriteria
{
    public $user;

    public $query;

    public $sort;

    public $order;

    public function __construct($user, $query, $sort, $order)
    {
        $this->user = $user;
        $this->query = $query;
        $this->sort = $sort;
        $this->order = $order;
    }
}
