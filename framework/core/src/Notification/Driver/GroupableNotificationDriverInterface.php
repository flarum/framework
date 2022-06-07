<?php

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
