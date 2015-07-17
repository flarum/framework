<?php namespace Flarum;

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
}
