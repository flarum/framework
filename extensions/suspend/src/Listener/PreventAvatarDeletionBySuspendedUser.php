<?php

namespace Flarum\Suspend\Listener;

use Flarum\User\Exception\PermissionDeniedException;

class PreventAvatarDeletionBySuspendedUser
{
    public function handle($event)
    {
        $actor = $event->actor;
        $user = $event->user;

        if ($actor->id === $user->id && $user->suspended_until) {
            throw new PermissionDeniedException();
        }
    }
}
