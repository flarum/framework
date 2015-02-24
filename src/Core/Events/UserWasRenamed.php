<?php namespace Flarum\Core\Events;

use Flarum\Core\Models\User;

class UserWasRenamed
{
    public $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }
}
