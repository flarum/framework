<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Push;

use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Notification\Driver\NotificationDriverInterface;

class NotificationDriver implements NotificationDriverInterface
{
    public function send(BlueprintInterface $blueprint, array $users): void
    {
        // The broadcast is intentionally NOT dispatched here. send() runs from
        // NotificationSyncer::sync(), before the notification records are inserted (that happens
        // in core's own queued Notification\Job\SendNotificationsJob). Dispatching the broadcast
        // here races that insert and, on the async queue realtime requires, frequently looked the
        // record up before it existed — dropping the toast.
        //
        // Instead we broadcast from the Notification\Event\Sent listener
        // (Push\Listener\BroadcastNotifications), which fires *after* the records are inserted.
        // The driver is still registered so 'realtime' is offered as a notification method and its
        // per-type preference is created via registerType().
    }

    public function registerType(string $blueprintClass, array $driversEnabledByDefault): void
    {
        // ...
    }
}
