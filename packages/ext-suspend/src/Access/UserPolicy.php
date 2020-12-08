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
    /**
     * @param User $actor
     * @param User $user
     * @return bool|null
     */
    public function suspend(User $actor, User $user)
    {
        if ($user->isAdmin() || $user->id === $actor->id) {
            return $this->deny();
        }
    }
}
