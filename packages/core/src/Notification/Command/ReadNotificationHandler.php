<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Notification\Command;

use Flarum\Notification\Notification;
use Flarum\User\AssertPermissionTrait;

class ReadNotificationHandler
{
    use AssertPermissionTrait;

    /**
     * @param ReadNotification $command
     * @return \Flarum\Notification\Notification
     * @throws \Flarum\User\Exception\PermissionDeniedException
     */
    public function handle(ReadNotification $command)
    {
        $actor = $command->actor;

        $this->assertRegistered($actor);

        $notification = Notification::where('user_id', $actor->id)->findOrFail($command->notificationId);

        Notification::where([
            'user_id' => $actor->id,
            'type' => $notification->type,
            'subject_id' => $notification->subject_id
        ])
            ->update(['is_read' => true]);

        $notification->is_read = true;

        return $notification;
    }
}
