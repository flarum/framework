<?php namespace Flarum\Events;

use Flarum\Core\Users\User;

class UserGroupsWereChanged
{
    /**
     * The user whose groups were changed.
     *
     * @var User
     */
    public $user;

    /**
     * @var Flarum\Core\Groups\Group[]
     */
    public $oldGroups;

    /**
     * @param User $user The user whose groups were changed.
     * @param Flarum\Core\Groups\Group[] $user
     */
    public function __construct(User $user, array $oldGroups)
    {
        $this->user = $user;
        $this->oldGroups = $oldGroups;
    }
}
