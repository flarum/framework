<?php 
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Suspend\Listeners;

use Flarum\Events\UserWillBeSaved;
use Flarum\Events\ModelAllow;
use Flarum\Events\GetUserGroups;
use Flarum\Core\Users\User;
use Flarum\Core\Groups\Group;
use Carbon\Carbon;

class PersistData
{
    public function subscribe($events)
    {
        $events->listen(UserWillBeSaved::class, [$this, 'whenUserWillBeSaved']);
        $events->listen(ModelAllow::class, [$this, 'disallowAdminSuspension']);
        $events->listen(GetUserGroups::class, [$this, 'revokePermissions']);
    }

    public function whenUserWillBeSaved(UserWillBeSaved $event)
    {
        $attributes = array_get($event->data, 'attributes', []);

        if (array_key_exists('suspendUntil', $attributes)) {
            $suspendUntil = $attributes['suspendUntil'];
            $user = $event->user;
            $actor = $event->actor;

            $user->assertCan($actor, 'suspend');

            $user->suspend_until = new Carbon($suspendUntil);
        }
    }

    public function disallowAdminSuspension(ModelAllow $event)
    {
        if ($event->model instanceof User && $event->action === 'suspend') {
            if ($event->model->isAdmin() || $event->model->id === $event->actor->id) {
                return false;
            }
        }
    }

    public function revokePermissions(GetUserGroups $event)
    {
        $suspendUntil = $event->user->suspend_until;

        if ($suspendUntil && $suspendUntil->gt(Carbon::now())) {
            $event->groupIds = [Group::GUEST_ID];
        }
    }
}
