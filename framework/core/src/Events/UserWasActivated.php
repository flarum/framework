<?php namespace Flarum\Events;

use Flarum\Core\Users\User;

class UserWasActivated
{
    /**
     * The user whose account was activated.
     *
     * @var User
     */
    public $user;

    /**
     * @param User $user The user whose account was activated.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }
}
