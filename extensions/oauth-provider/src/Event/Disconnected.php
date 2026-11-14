<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Event;

use Flarum\OAuthProvider\Models\Client;
use Flarum\User\User;

/**
 * A member disconnected an application they had connected.
 */
class Disconnected
{
    public function __construct(
        public User $user,
        public Client $client,
    ) {
    }
}
