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
use Illuminate\Contracts\Queue\Queue;

class NotificationDriver implements NotificationDriverInterface
{
    public function __construct(protected Queue $queue)
    {
    }

    public function send(BlueprintInterface $blueprint, array $users): void
    {
        if (count($users)) {
            $this->queue->push(new Jobs\SendNotificationsJob($blueprint, $users));
        }
    }

    public function registerType(string $blueprintClass, array $driversEnabledByDefault): void
    {
        // ...
    }
}
