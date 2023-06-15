<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

if (! defined('LARAVEL_VERSION')) {
    define('LARAVEL_VERSION', '10.0');
}

if (! function_exists('database_path')) {
    function database_path($path = ''): string
    {
        return __DIR__."/../../$path";
    }
}

$site = (new \Flarum\Testing\integration\Setup\Bootstrapper())->run();
$site->bootApp();
