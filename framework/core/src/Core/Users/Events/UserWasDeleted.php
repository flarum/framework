<?php namespace Flarum\Core\Users\Events;

use Flarum\Core\Users\User;

class UserWasDeleted
{
    /**
     * The user who was deleted.
     *
     * @var User
     */
    public $user;

    /**
     * @param User $user The user who was deleted.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }
}
