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
use Flarum\Event\UserWillBeSaved;
use Illuminate\Contracts\Events\Dispatcher;

class AdminGroupLockingPrevention
{
    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(UserWillBeSaved::class, [$this, 'whenUserWillBeSaved']);
    }

    /**
     * @param UserWillBeSaved $event
     * @throws PermissionDeniedException
     */
    public function whenUserWillBeSaved(UserWillBeSaved $event)
    {
        $actor = $event->actor;
        $user = $event->user;
        $groups = array_get($event->data, 'relationships.groups.data');

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
