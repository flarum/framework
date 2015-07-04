<?php namespace Flarum\Core\Users\Events;

use Flarum\Core\Users\User;

class UserWillBeDeleted
{
    /**
     * The user who will be deleted.
     *
     * @var User
     */
    public $user;

    /**
     * The user who is performing the action.
     *
     * @var User
     */
    public $actor;

    /**
     * Any user input associated with the command.
     *
     * @var array
     */
    public $data;

    /**
     * @param User $user The user who will be deleted.
     * @param User $actor The user performing the action.
     * @param array $data Any user input associated with the command.
     */
    public function __construct(User $user, User $actor, array $data)
    {
        $this->user = $user;
        $this->actor = $actor;
        $this->data = $data;
    }
}
