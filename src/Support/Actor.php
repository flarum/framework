<?php namespace Flarum\Support;

use Flarum\Core\Models\Guest;

class Actor
{
    protected $user;

    public function getUser()
    {
        return $this->user ?: new Guest;
    }

    public function setUser($user)
    {
        $this->user = $user;
    }

    public function isAuthenticated()
    {
        return (bool) $this->user;
    }
}
