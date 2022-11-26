<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Notification\Event;

use DateTime;
use Flarum\Notification\Notification;
use Flarum\User\User;

class Read
{
    /**
     * @var User
     */
    public $actor;

    /**
     * @var Notification
     */
    public $notification;

    /**
     * @var DateTime
     */
    public $timestamp;

    public function __construct(User $user, Notification $notification, DateTime $timestamp)
    {
        $this->actor = $user;
        $this->notification = $notification;
        $this->timestamp = $timestamp;
    }
}
