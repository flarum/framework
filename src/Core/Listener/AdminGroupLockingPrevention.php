<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Listener;

use Flarum\Core\Exception\PermissionDeniedException;
use Flarum\Core\Group;
use Flarum\Event\UserGroupsWereChanged;
use Illuminate\Contracts\Events\Dispatcher;

class AdminGroupLockingPrevention
{
    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(UserGroupsWereChanged::class, [$this, 'whenUserGroupsWereChanged']);
    }

    /**
     * @param UserGroupsWereChanged $event
     * @throws PermissionDeniedException
     */
    public function whenUserGroupsWereChanged(UserGroupsWereChanged $event)
    {
        if (! $event->actor) {
            return;
        }

        $actor = $event->actor;
        $user = $event->user;

        // Prevent an admin from removing their admin permission via the API
        if ($actor->id === $user->id && $actor->isAdmin() && ! $user->isAdmin()) {
            $userGroups = $user->groups()->get(['group_id'])->all();

            $newGroups = array_map(function ($group) {
                return $group->group_id;
            }, $userGroups);
            $newGroups[] = Group::ADMINISTRATOR_ID;

            $user->groups()->sync($newGroups);
        }
    }
}
