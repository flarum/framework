<?php namespace Flarum\Forum\Events;

use Flarum\Core\Models\User;

class UserLoggedOut
{
    public $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }
}
