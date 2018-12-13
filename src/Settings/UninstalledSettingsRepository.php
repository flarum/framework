<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Settings;

class UninstalledSettingsRepository implements SettingsRepositoryInterface
{
    public function all()
    {
        return [];
    }

    public function get($key, $default = null)
    {
        return $default;
    }

    public function set($key, $value)
    {
        // Do nothing
    }

    public function delete($keyLike)
    {
        // Do nothing
    }
}
