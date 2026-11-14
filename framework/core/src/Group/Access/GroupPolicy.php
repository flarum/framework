<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Group\Access;

use Flarum\Group\Group;
use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;

class GroupPolicy extends AbstractPolicy
{
    public function can(User $actor, string $ability): ?string
    {
        if ($actor->hasPermission('group.'.$ability)) {
            return $this->allow();
        }

        return null;
    }

    /**
     * The admin, guest and member groups are built in: core relies on them (`Guest`,
     * `User::groups()`), so nobody may delete them, administrators included.
     */
    public function delete(User $actor, Group $group): ?string
    {
        if (in_array((int) $group->id, [Group::ADMINISTRATOR_ID, Group::GUEST_ID, Group::MEMBER_ID], true)) {
            return $this->forceDeny();
        }

        return null;
    }
}
