<?php namespace Flarum\Core\Events;

use Flarum\Core\Models\User;

class UserWasDeleted
{
    public $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }
}
