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
        // Check the actor's permission first. When they cannot edit credentials
        // at all (guests and normal users — the vast majority of serialized
        // actors), we return no opinion without touching $user->isAdmin(), which
        // would otherwise lazy-load $user->groups once per serialized user and
        // cause an N+1 on render paths that serialize many users (e.g. post
        // authors, likers). See flarum/framework#4724.
        if (! $actor->hasPermission('user.editCredentials')) {
            return null;
        }

        // The actor may edit credentials, but must not edit an admin's unless
        // they are an admin themselves.
        if ($user->isAdmin() && ! $actor->isAdmin()) {
            return $this->deny();
        }

        return $this->allow();
    }
}
