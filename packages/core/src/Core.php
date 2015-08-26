<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum;

class Core
{
    public static function isInstalled()
    {
        return app()->bound('flarum.config');
    }

    public static function inDebugMode()
    {
        return static::isInstalled() && app('flarum.config')['debug'];
    }

    public static function config($key, $default = null)
    {
        if (! static::isInstalled()) {
            return $default;
        }

        return app('Flarum\Core\Settings\SettingsRepository')->get($key, $default);
    }

    public static function url($name = null)
    {
        $url = app('flarum.config')['url'];

        if ($name) {
            $url .= '/' . app('flarum.config')['paths'][$name];
        }

        return $url;
    }
}
