<?php namespace Flarum\Core\Search\Users;

class UserSearchResults
{
    protected $users;

    protected $areMoreResults;

    public function __construct($users, $areMoreResults)
    {
        $this->users = $users;
        $this->areMoreResults = $areMoreResults;
    }

    public function getUsers()
    {
        return $this->users;
    }

    public function areMoreResults()
    {
        return $this->areMoreResults;
    }
}
