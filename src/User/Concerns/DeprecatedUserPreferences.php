<?php

namespace Flarum\User\Concerns;

use Flarum\User\User;
use Illuminate\Support\Arr;

trait DeprecatedUserPreferences
{


    /**
     * Get the values of all registered preferences for this user, by
     * transforming their stored preferences and merging them with the defaults.
     *
     * @param string $value
     * @return array
     * @deprecated 0.1.0-beta.11: `users.preferences` is no longer used.
     */
    public function getPreferencesAttribute($value)
    {
        $defaults = array_map(function ($value) {
            return $value['default'];
        }, static::$preferences);

        $user = Arr::only($this->notificationPreferences->toArray(), array_keys(static::$preferences));

        return array_merge($defaults, $user);
    }

    /**
     * Encode an array of preferences for storage in the database.
     *
     * @param mixed $value
     * @deprecated 0.1.0-beta.11: `users.preferences` is no longer used.
     */
    public function setPreferencesAttribute($value)
    {
    }

    /**
     * Get the value of a preference for this user.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     * @deprecated 0.1.0-beta.11: `users.preferences` is no longer used.
     */
    public function getPreference($key, $default = null)
    {
        return Arr::get($this->preferences, $key, $default);
    }

    /**
     * Set the value of a preference for this user.
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     * @deprecated 0.1.0-beta.11: `users.preferences` is no longer used.
     */
    public function setPreference($key, $value)
    {
    }

    /**
     * Get the key for a preference which flags whether or not the user will
     * receive a notification for $type via $method.
     *
     * @param string $type
     * @param string $method
     * @return string
     * @deprecated 0.1.0-beta.11: `users.preferences` is no longer used, use NotificationPreference::getPreferenceKey.
     */
    public static function getNotificationPreferenceKey($type, $method)
    {
    }
}
