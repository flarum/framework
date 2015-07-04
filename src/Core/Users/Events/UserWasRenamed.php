<?php namespace Flarum\Core\Users\Events;

use Flarum\Core\Users\User;

class UserWasRenamed
{
    /**
     * The user who was renamed.
     *
     * @var User
     */
    public $user;

    /**
     * @param User $user The user who was renamed.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }
}
