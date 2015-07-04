<?php namespace Flarum\Core\Users\Events;

use Flarum\Core\Users\User;

class AvatarWillBeSaved
{
    /**
     * The user whose avatar will be saved.
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
     * The path to the avatar that will be saved.
     *
     * @var string
     */
    public $path;

    /**
     * @param User $user The user whose avatar will be saved.
     * @param User $actor The user performing the action.
     * @param string $path The path to the avatar that will be saved.
     */
    public function __construct(User $user, User $actor, $path)
    {
        $this->user = $user;
        $this->actor = $actor;
        $this->path = $path;
    }
}
