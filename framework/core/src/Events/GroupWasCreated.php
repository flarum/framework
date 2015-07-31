<?php namespace Flarum\Events;

use Flarum\Core\Groups\Group;

class GroupWasCreated
{
    /**
     * The group that was created.
     *
     * @var Group
     */
    public $group;

    /**
     * @param Group $group The group that was created.
     */
    public function __construct(Group $group)
    {
        $this->group = $group;
    }
}
