<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Settings;

class TemporarySettingsRepository implements SettingsRepositoryInterface
{
    protected $settings = [];

    public function all(): array
    {
        return $this->settings;
    }

    public function get($key, $default = null)
    {
        if (array_key_exists($key, $this->settings)) {
            return $this->settings[$key];
        } else {
            return $default;
        }
    }

    public function set($key, $value) {
        $this->settings[$key] = $value;
    }

    public function delete($keyLike) {
        unset($this->settings[$key]);
    }
}
