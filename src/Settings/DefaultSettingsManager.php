<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Settings;

use Illuminate\Support\Arr;

class DefaultSettingsManager
{
    protected $defaults = [];

    public function get($key, $default = null)
    {
        return Arr::get($this->defaults, $key, $default);
    }

    public function set($key, $value)
    {
        $this->defaults[$key] = $value;
    }
}
