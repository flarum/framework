<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Notifications\Commands;

use Flarum\Core\Notifications\Notification;
use Flarum\Core\Notifications\NotificationRepository;

class ReadAllNotificationsHandler
{
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
     *
     * @return void
     */
    public function handle(ReadAllNotifications $command)
    {
        $actor = $command->actor;

        if ($actor->isGuest()) {
            throw new PermissionDeniedException;
        }

        $this->notifications->markAllAsRead($actor);
    }
}
