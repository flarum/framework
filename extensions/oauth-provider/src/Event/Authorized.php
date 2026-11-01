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
 * A member authorized an application: they just approved it ($consented),
 * or an approval they gave earlier covered the request.
 */
class Authorized
{
    public function __construct(
        public User $user,
        public Client $client,
        public bool $consented,
    ) {
    }
}
