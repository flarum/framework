<?php namespace Flarum\Core\Handlers\Commands;

use Flarum\Core\Models\Notification;
use Flarum\Core\Exceptions\PermissionDeniedException;
use Flarum\Core\Support\DispatchesEvents;

class ReadNotificationCommandHandler
{
    use DispatchesEvents;

    public function handle($command)
    {
        $user = $command->user;

        if (! $user->exists) {
            throw new PermissionDeniedException;
        }

        $notification = Notification::where('user_id', $user->id)->findOrFail($command->notificationId);

        $notification->read();

        $notification->save();
        $this->dispatchEventsFor($notification);

        return $notification;
    }
}
