<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation;

use InvalidArgumentException;
use RuntimeException;

class Site
{
    /**
     * @param array $paths
     * @return SiteInterface
     */
    public static function fromPaths(array $paths)
    {
        if (! isset($paths['base'], $paths['public'], $paths['storage'])) {
            throw new InvalidArgumentException(
                'Paths array requires keys base, public and storage'
            );
        }

        date_default_timezone_set('UTC');

        if (static::hasConfigFile($paths['base'])) {
            return (
                new InstalledSite($paths, static::loadConfig($paths['base']))
            )->extendWith(static::loadExtenders($paths['base']));
        } else {
            return new UninstalledSite($paths);
        }
    }

    private static function hasConfigFile($basePath)
    {
        return file_exists("$basePath/config.php");
    }

    private static function loadConfig($basePath): array
    {
        $config = include "$basePath/config.php";

        if (! is_array($config)) {
            throw new RuntimeException('config.php should return an array');
        }

        return $config;
    }

    private static function loadExtenders($basePath): array
    {
        $extenderFile = "$basePath/extend.php";

        if (! file_exists($extenderFile)) {
            return [];
        }

        $extenders = require $extenderFile;

        if (! is_array($extenders)) {
            return [];
        }

        return array_flatten($extenders);
    }
}
