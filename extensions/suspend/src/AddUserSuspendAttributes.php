<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Suspend;

use Flarum\Api\Serializer\UserSerializer;
use Flarum\User\User;

class AddUserSuspendAttributes
{
    public function __invoke(UserSerializer $serializer, User $user)
    {
        $attributes = [];
        $canSuspend = $serializer->getActor()->can('suspend', $user);

        if ($canSuspend) {
            $attributes['suspendedUntil'] = $serializer->formatDate($user->suspended_until);
        }

        $attributes['canSuspend'] = $canSuspend;

        return $attributes;
    }
}
