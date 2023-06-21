<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\Access;

use Flarum\User\User;

class UserPolicy extends AbstractPolicy
{
    public function can(User $actor, string $ability): ?string
    {
        if ($actor->hasPermission('user.'.$ability)) {
            return $this->allow();
        }

        return null;
    }

    public function editCredentials(User $actor, User $user): ?string
    {
        if ($user->isAdmin() && ! $actor->isAdmin()) {
            return $this->deny();
        }

        if ($actor->hasPermission('user.editCredentials')) {
            return $this->allow();
        }

        return null;
    }
}
