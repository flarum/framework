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

class Deleted
{
    /**
     * @var \Flarum\Group\Group
     */
    public $group;

    /**
     * @var User
     */
    public $actor;

    /**
     * @param \Flarum\Group\Group $group
     * @param User $actor
     */
    public function __construct(Group $group, User $actor = null)
    {
        $this->group = $group;
        $this->actor = $actor;
    }
}
