<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\Event;

use Flarum\Http\AccessToken;
use Flarum\User\User;

class LoggedIn
{
    public function __construct(
        public User $user,
        #[\SensitiveParameter] public AccessToken $token
    ) {
    }
}
