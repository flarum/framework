<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Event;

use Flarum\User\User;

/**
 * A member disconnected every application they had connected at once. Not
 * dispatched for logging out everywhere, which disconnects them as well.
 */
class DisconnectedAll
{
    public function __construct(
        public User $user,
    ) {
    }
}
