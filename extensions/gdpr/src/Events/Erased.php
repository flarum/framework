<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Gdpr\Events;

use Flarum\User\User;

class Erased
{
    public function __construct(
        public string $username,
        public string $email,
        public string $mode,
        public User $user
    ) {
    }
}
