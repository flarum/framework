<?php namespace Flarum\Core\Notifications\Commands;

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

        $notification->read();
        $notification->save();

        return $notification;
    }
}
