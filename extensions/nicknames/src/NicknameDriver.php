<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Nicknames;

use Flarum\User\DisplayName\DriverInterface;
use Flarum\User\User;

class NicknameDriver implements DriverInterface
{
    public function displayName(User $user): string
    {
        return $user->nickname ? $user->nickname : $user->username;
    }
}
