<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\Event;

use Flarum\User\User;

class EmailChangeRequested
{
    /**
     * The user who requested the email change.
     *
     * @var User
     */
    public $user;

    /**
     * The email they requested to change to.
     *
     * @var string
     */
    public $email;

    /**
     * @param User $user The user who requested the email change.
     * @param string $email The email they requested to change to.
     */
    public function __construct(User $user, $email)
    {
        $this->user = $user;
        $this->email = $email;
    }
}
