<?php namespace Flarum;

class Core
{
    public static function isInstalled()
    {
        return file_exists(base_path('../config.php'));
    }
}
