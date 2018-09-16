<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Suspend\Listener;

use Carbon\Carbon;
use Flarum\Event\PrepareUserGroups;
use Flarum\Group\Group;
use Illuminate\Contracts\Events\Dispatcher;

class RevokeAccessFromSuspendedUsers
{
    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(PrepareUserGroups::class, [$this, 'prepareUserGroups']);
    }

    /**
     * @param PrepareUserGroups $event
     */
    public function prepareUserGroups(PrepareUserGroups $event)
    {
        $suspendedUntil = $event->user->suspended_until;

        if ($suspendedUntil && $suspendedUntil->gt(Carbon::now())) {
            $event->groupIds = [Group::GUEST_ID];
        }
    }
}
