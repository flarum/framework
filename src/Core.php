<?php namespace Flarum;

use DB;

class Core
{
    public static function isInstalled()
    {
        return file_exists(base_path('../config.php'));
    }

    public static function config($key, $default = null)
    {
        if (! static::isInstalled()) {
            return $default;
        }

        if (is_null($value = app('db')->table('config')->where('key', $key)->pluck('value'))) {
            $value = $default;
        }

        return $value;
    }
}
