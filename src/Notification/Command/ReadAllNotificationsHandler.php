<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Notification\Command;

use Flarum\Notification\NotificationRepository;
use Flarum\User\AssertPermissionTrait;

class ReadAllNotificationsHandler
{
    use AssertPermissionTrait;

    /**
     * @var NotificationRepository
     */
    protected $notifications;

    /**
     * @param NotificationRepository $notifications
     */
    public function __construct(NotificationRepository $notifications)
    {
        $this->notifications = $notifications;
    }

    /**
     * @param ReadAllNotifications $command
     * @throws \Flarum\User\Exception\PermissionDeniedException
     */
    public function handle(ReadAllNotifications $command)
    {
        $actor = $command->actor;

        $this->assertRegistered($actor);

        $this->notifications->markAllAsRead($actor);
    }
}
