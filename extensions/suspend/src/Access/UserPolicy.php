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
        // Check the actor's permission first. Whether a user can be suspended
        // is serialized with every user, and checking $user->isAdmin() loads
        // their groups; an actor who can't suspend anyone doesn't need it.
        // Suspending an admin is also refused when it's saved, whatever any
        // policy allows. See flarum/framework#4724 for the same pattern.
        if (! $actor->hasPermission('user.suspend')) {
            return null;
        }

        if ($user->isAdmin() || $user->id === $actor->id) {
            return $this->deny();
        }

        return null;
    }
}
