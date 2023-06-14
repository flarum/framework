<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Notification\Driver;

use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Notification\Job\SendNotificationsJob;
use Flarum\User\User;
use Illuminate\Contracts\Queue\Queue;

class AlertNotificationDriver implements NotificationDriverInterface
{
    public function __construct(
        private readonly Queue $queue
    ) {
    }

    public function send(BlueprintInterface $blueprint, array $users): void
    {
        if (count($users)) {
            $this->queue->push(new SendNotificationsJob($blueprint, $users));
        }
    }

    public function registerType(string $blueprintClass, array $driversEnabledByDefault): void
    {
        User::registerPreference(
            User::getNotificationPreferenceKey($blueprintClass::getType(), 'alert'),
            boolval(...),
            in_array('alert', $driversEnabledByDefault)
        );
    }
}
