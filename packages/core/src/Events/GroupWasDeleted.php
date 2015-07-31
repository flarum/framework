<?php namespace Flarum\Events;

use Flarum\Core\Groups\Group;

class GroupWasDeleted
{
    /**
     * The group that was deleted.
     *
     * @var Group
     */
    public $group;

    /**
     * @param Group $group The group that was deleted.
     */
    public function __construct(Group $group)
    {
        $this->group = $group;
    }
}
