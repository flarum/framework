<?php namespace Flarum\Events;

use Flarum\Core\Users\User;

class UserPasswordWasChanged
{
    /**
     * The user whose password was changed.
     *
     * @var User
     */
    public $user;

    /**
     * @param User $user The user whose password was changed.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }
}
