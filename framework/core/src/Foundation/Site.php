<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation;

use Illuminate\Support\Arr;
use RuntimeException;

class Site
{
    public static function fromPaths(array $paths): SiteInterface
    {
        $paths = new Paths($paths);

        define('FLARUM_START', microtime(true));

        date_default_timezone_set('UTC');

        if (! static::hasConfigFile($paths->base)) {
            // Instantiate site instance for new installations,
            // fallback to localhost for validation of Config for instance in CLI.
            return new UninstalledSite(
                $paths,
                Arr::get($_SERVER, 'REQUEST_URI', 'http://localhost')
            );
        }

        $config = static::loadConfig($paths->base);

        ini_set('display_errors', 0);

        if (! $config->inDebugMode() && function_exists('error_reporting')) {
            error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
        }

        return (
            new InstalledSite($paths, $config)
        )->extendWith(static::loadExtenders($paths->base));
    }

    protected static function hasConfigFile(string $basePath): bool
    {
        return file_exists("$basePath/config.php");
    }

    protected static function loadConfig(string $basePath): Config
    {
        $config = include "$basePath/config.php";

        if (! is_array($config)) {
            throw new RuntimeException('config.php should return an array');
        }

        return new Config($config);
    }

    protected static function loadExtenders(string $basePath): array
    {
        $extenderFile = "$basePath/extend.php";

        if (! file_exists($extenderFile)) {
            return [];
        }

        $extenders = require $extenderFile;

        if (! is_array($extenders)) {
            return [];
        }

        return Arr::flatten($extenders);
    }
}
