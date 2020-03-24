<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\Concerns;

trait DeprecatedUserNotificationPreferences
{

    /**
     * Encode an array of preferences for storage in the database.
     *
     * @param mixed $value
     * @deprecated 0.1.0-beta.13: `users.preferences` is no longer used.
     */
    public function setPreferencesAttribute($value)
    {
    }

    /**
     * Get the key for a preference which flags whether or not the user will
     * receive a notification for $type via $method.
     *
     * @param string $type
     * @param string $method
     * @return string
     * @deprecated 0.1.0-beta.13: `users.preferences` is no longer used, use \Flarum\User\NotificationPreference.
     */
    public static function getNotificationPreferenceKey($type, $method)
    {
    }
}
