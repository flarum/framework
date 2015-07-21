<?php namespace Flarum\Events;

use Flarum\Core\Users\User;

class UserLoggedOut
{
    public $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }
}
