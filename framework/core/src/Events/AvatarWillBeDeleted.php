<?php namespace Flarum\Events;

use Flarum\Core\Users\User;

class AvatarWillBeDeleted
{
    /**
     * The user whose avatar will be deleted.
     *
     * @var User
     */
    public $user;

    /**
     * The user performing the action.
     *
     * @var User
     */
    public $actor;

    /**
     * @param User $user The user whose avatar will be deleted.
     * @param User $actor The user performing the action.
     */
    public function __construct(User $user, User $actor)
    {
        $this->user = $user;
        $this->actor = $actor;
    }
}
