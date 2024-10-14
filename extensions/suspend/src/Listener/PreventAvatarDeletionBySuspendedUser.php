<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Suspend\Listener;

use Flarum\User\Exception\PermissionDeniedException;

class PreventAvatarDeletionBySuspendedUser
{
    public function handle($event)
    {
        $actor = $event->actor;
        $user = $event->user;

        if (! $actor->isAdmin() && $actor->id === $user->id && $user->suspended_until && $user->suspended_until->isFuture()) {
            throw new PermissionDeniedException();
        }
    }
}
