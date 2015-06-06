<?php namespace Flarum\Core\Events;

use Flarum\Core\Models\User;

class UserEmailChangeWasRequested
{
    public $user;

    public $email;

    public function __construct(User $user, $email)
    {
        $this->user = $user;
        $this->email = $email;
    }
}
