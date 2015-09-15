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
use Flarum\Core\Exceptions\PermissionDeniedException;
use Flarum\Core\Support\DispatchesEvents;

class ReadNotificationHandler
{
    /**
     * @param ReadNotification $command
     * @return Notification
     * @throws \Flarum\Core\Exceptions\PermissionDeniedException
     */
    public function handle(ReadNotification $command)
    {
        $actor = $command->actor;

        if ($actor->isGuest()) {
            throw new PermissionDeniedException;
        }

        $notification = Notification::where('user_id', $actor->id)->findOrFail($command->notificationId);

        Notification::where([
            'user_id' => $actor->id,
            'type' => $notification->type,
            'subject_id' => $notification->subject_id
        ])
            ->update(['is_read' => true]);

        return $notification;
    }
}
