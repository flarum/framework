<?php namespace Flarum\Core\Search\Users;

class UserSearchResults
{
    protected $users;

    protected $areMoreResults;

    protected $total;

    public function __construct($users, $areMoreResults, $total)
    {
        $this->users = $users;
        $this->areMoreResults = $areMoreResults;
        $this->total = $total;
    }

    public function getUsers()
    {
        return $this->users;
    }

    public function getTotal()
    {
        return $this->total;
    }

    public function areMoreResults()
    {
        return $this->areMoreResults;
    }
}
