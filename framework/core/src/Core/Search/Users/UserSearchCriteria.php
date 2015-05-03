<?php namespace Flarum\Core\Search\Users;

class UserSearchCriteria
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
