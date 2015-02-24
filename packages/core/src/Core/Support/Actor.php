<?php namespace Flarum\Core\Support;

use Flarum\Core\Models\User;
use Flarum\Core\Models\Guest;

class Actor
{
    protected $user;

    public function getUser()
    {
        return $this->user ?: new Guest;
    }

    public function setUser(User $user)
    {
        $this->user = $user;
    }
}
