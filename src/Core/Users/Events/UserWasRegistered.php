<?php namespace Flarum\Core\Users\Events;

use Flarum\Core\Users\User;

class UserWasRegistered
{
    /**
     * The user who was registered.
     *
     * @var User
     */
    public $user;

    /**
     * @param User $user The user who was registered.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }
}
