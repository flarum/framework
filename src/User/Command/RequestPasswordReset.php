<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\Command;

class RequestPasswordReset
{
    /**
     * The email of the user to request a password reset for.
     *
     * @var string
     */
    public $email;

    /**
     * @param string $email The email of the user to request a password reset for.
     */
    public function __construct($email)
    {
        $this->email = $email;
    }
}
