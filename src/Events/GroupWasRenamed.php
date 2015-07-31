<?php namespace Flarum\Events;

use Flarum\Core\Groups\Group;

class GroupWasRenamed
{
    /**
     * The group that was renamed.
     *
     * @var Group
     */
    public $group;

    /**
     * @param Group $group The group that was renamed.
     */
    public function __construct(Group $group)
    {
        $this->group = $group;
    }
}
