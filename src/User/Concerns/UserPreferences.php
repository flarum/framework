<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\Concerns;

use Illuminate\Support\Arr;

trait UserPreferences
{
    /**
     * An array of registered user preferences. Each preference is defined with
     * a key, and its value is an array containing the following keys:.
     *
     * - transformer: a callback that confines the value of the preference
     * - default: a default value if the preference isn't set
     *
     * @var array
     */
    protected static $preferences = [];

    /**
     * Get the values of all registered preferences for this user, by
     * transforming their stored preferences and merging them with the defaults.
     *
     * @param string $value
     * @return array
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
     * Get the value of a preference for this user.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getPreference($key, $default = null)
    {
        return $this->$key ?? $default;
    }

    /**
     * Set the value of a preference for this user.
     *
     * @param string $key
     * @param mixed $value
     */
    public function setPreference($key, $value)
    {
        $preference = static::$preferences[$key];

        // If a user preference is registered, transform the value.
        if ($preference) {
            $value = $value === null ? $preference['default'] : $value;
            $value = $preference['transformer']($value);
        }

        $this->{$key} = $value;
    }

    /**
     * Register a preference with a transformer and a default value.
     *
     * @param string $key
     * @param callable $transformer
     * @param mixed $default
     */
    public static function addPreference($key, callable $transformer = null, $default = null)
    {
        static::$preferences[$key] = compact('transformer', 'default');
    }
}
