<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Events;

use Flarum\Core\Groups\Group;
use Flarum\Core\Users\User;

class GroupWillBeDeleted
{
    /**
     * The group who will be deleted.
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
     * Any group input associated with the command.
     *
     * @var array
     */
    public $data;

    /**
     * @param Group $group The group who will be deleted.
     * @param User $actor The user performing the action.
     * @param array $data Any group input associated with the command.
     */
    public function __construct(Group $group, User $actor, array $data)
    {
        $this->group = $group;
        $this->actor = $actor;
        $this->data = $data;
    }
}
