<?php namespace Flarum\Core\Groups\Commands;

use Flarum\Core\Groups\Group;
use Flarum\Core\Users\User;

class EditGroup
{
    /**
     * The ID of the group to edit.
     *
     * @var int
     */
    public $groupId;

    /**
     * The user performing the action.
     *
     * @var User
     */
    public $actor;

    /**
     * The attributes to update on the post.
     *
     * @var array
     */
    public $data;

    /**
     * @param int $groupId The ID of the group to edit.
     * @param User $actor The user performing the action.
     * @param array $data The attributes to update on the post.
     */
    public function __construct($groupId, User $actor, array $data)
    {
        $this->groupId = $groupId;
        $this->actor = $actor;
        $this->data = $data;
    }
}
