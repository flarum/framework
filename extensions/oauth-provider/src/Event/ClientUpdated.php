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
 * An admin edited a client.
 */
class ClientUpdated
{
    public function __construct(
        public Client $client,
        public User $actor,
    ) {
    }
}
