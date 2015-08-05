<?php namespace Flarum\Events;

use Flarum\Core\Users\User;

/**
 * The `GetUserGroups` event
 */
class GetUserGroups
{
    /**
     * @var User
     */
    public $user;

    /**
     * @var array
     */
    public $groupIds;

    /**
     * @param User $user
     * @param array $groupIds
     */
    public function __construct(User $user, array &$groupIds)
    {
        $this->user = $user;
        $this->groupIds = &$groupIds;
    }
}
