<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Group\Event;

use Flarum\Group\Group;
use Flarum\User\User;

class Deleting
{
    /**
     * The group that will be deleted.
     *
     * @var Group
     */
    public $group;

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
     * @param Group $group The group that will be deleted.
     * @param User $actor The user performing the action.
     * @param array $data Any user input associated with the command.
     */
    public function __construct(Group $group, User $actor, array $data)
    {
        $this->group = $group;
        $this->actor = $actor;
        $this->data = $data;
    }
}
