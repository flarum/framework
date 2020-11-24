<?php

namespace Flarum\Nicknames;

use Flarum\User\DisplayName\DriverInterface;
use Flarum\User\User;

class NicknameDriver implements DriverInterface {

    public function displayName(User $user): string
    {
        return $user->nickname ? $user->nickname : $user->username;
    }
}
