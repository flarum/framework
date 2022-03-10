<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Group\Command;

use Flarum\User\User;

class DeleteGroup
{
    /**
     * The ID of the group to delete.
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
     * Any other group input associated with the action. This is unused by
     * default, but may be used by extensions.
     *
     * @var array
     */
    public $data;

    /**
     * @param int $groupId The ID of the group to delete.
     * @param User $actor The user performing the action.
     * @param array $data Any other group input associated with the action. This
     *     is unused by default, but may be used by extensions.
     */
    public function __construct($groupId, User $actor, array $data = [])
    {
        $this->groupId = $groupId;
        $this->actor = $actor;
        $this->data = $data;
    }
}
