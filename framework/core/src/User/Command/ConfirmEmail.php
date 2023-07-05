<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\Command;

class ConfirmEmail
{
    public function __construct(
        /**
         * The email confirmation token.
         */
        public string $token
    ) {
    }
}
