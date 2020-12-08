<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Suspend;

use Carbon\Carbon;
use Flarum\Group\Group;
use Flarum\User\User;

class RevokeAccessFromSuspendedUsers
{
    /**
     * @param User $user
     * @param array $groupIds
     */
    public function __invoke(User $user, array $groupIds)
    {
        $suspendedUntil = $user->suspended_until;

        if ($suspendedUntil && $suspendedUntil->gt(Carbon::now())) {
            return [Group::GUEST_ID];
        }

        return $groupIds;
    }
}
