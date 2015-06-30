<?php namespace Flarum;

class Core
{
    public static function isInstalled()
    {
        return app()->bound('flarum.config');
    }

    public static function inDebugMode()
    {
        return env('APP_DEBUG');
    }

    public static function config($key, $default = null)
    {
        if (! static::isInstalled()) {
            return $default;
        }

        if (is_null($value = app('flarum.db')->table('config')->where('key', $key)->pluck('value'))) {
            $value = $default;
        }

        return $value;
    }
}
