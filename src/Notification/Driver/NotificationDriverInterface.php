<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Notification\Driver;

use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\User\User;

interface NotificationDriverInterface
{
    /**
     * Enqueues the job for this notification.
     *
     * @param BlueprintInterface $blueprint
     * @param User[] $users
     * @return void
     */
    public function send(BlueprintInterface $blueprint, array $users): void;

    /**
     * Logic for adding a user preference.
     *
     * @param string $blueprintClass
     * @param bool $default
     * @return void
     */
    public function addUserPreference(string $blueprintClass, bool $default): void;
}
