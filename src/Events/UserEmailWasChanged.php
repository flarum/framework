<?php namespace Flarum\Events;

use Flarum\Core\Users\User;

class UserEmailWasChanged
{
    /**
     * The user whose email was changed.
     *
     * @var User
     */
    public $user;

    /**
     * @param User $user The user whose email was changed.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }
}
