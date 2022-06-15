<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Notification\Driver;

interface GroupableNotificationDriverInterface extends NotificationDriverInterface
{
    /**
     * Implies the delay to wait before sending out notifications to allow blueprintGrouping to happen.
     *
     * @todo Enable for v2.0+
     */
    public function blueprintGroupingDelay(): ?\DateInterval;

    public function sendGrouped();
}
