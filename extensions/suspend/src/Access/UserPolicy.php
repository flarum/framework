<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Suspend\Access;

use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;

class UserPolicy extends AbstractPolicy
{
    public function suspend(User $actor, User $user): ?string
    {
        if ($user->isAdmin() || $user->id === $actor->id) {
            return $this->deny();
        }

        return null;
    }

    public function editAvatar(User $actor, User $user): ?string
    {
        if ($actor->suspended_until?->isFuture()) {
            return $this->deny();
        }

        return null;
    }
}
