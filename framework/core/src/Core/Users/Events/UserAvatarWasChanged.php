<?php namespace Flarum\Core\Users\Events;

use Flarum\Core\Users\User;

class UserAvatarWasChanged
{
    /**
     * The user whose avatar was changed.
     *
     * @var User
     */
    public $user;

    /**
     * @param User $user The user whose avatar was changed.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }
}
