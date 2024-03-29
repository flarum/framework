<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Pusher;

use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Notification\Driver\NotificationDriverInterface;
use Illuminate\Contracts\Queue\Queue;

class PusherNotificationDriver implements NotificationDriverInterface
{
    public function __construct(
        protected Queue $queue
    ) {
    }

    public function send(BlueprintInterface $blueprint, array $users): void
    {
        if (count($users)) {
            $this->queue->push(new SendPusherNotificationsJob($blueprint, $users));
        }
    }

    public function registerType(string $blueprintClass, array $driversEnabledByDefault): void
    {
        // ...
    }
}
