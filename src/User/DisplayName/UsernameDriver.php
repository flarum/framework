<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\DisplayName;

use Flarum\User\User;

/**
 * The default driver, which returns the user's username.
 */
class UsernameDriver implements DriverInterface
{
    public function displayName(User $user): string
    {
        return $user->username;
    }
}
