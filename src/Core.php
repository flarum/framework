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
        if (is_null($value = DB::table('config')->where('key', $key)->pluck('value'))) {
            $value = $default;
        }

        return $value;
    }
}
