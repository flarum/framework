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
 * A member authorized an application, but the authorization was withdrawn
 * before the application got a code: their session ended, or they
 * disconnected the application, while it ran.
 *
 * $approvalKept: the member approved the application on the consent form, and
 * the approval stays on their existing connection to it.
 */
class AuthorizationWithdrawn
{
    public function __construct(
        public User $user,
        public Client $client,
        public bool $approvalKept,
    ) {
    }
}
