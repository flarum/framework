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
        
        if (! isset($paths['config'])) {
            $paths['config'] = $paths['base'] . "/config.php";
        }
        
        if (! isset($paths['extend'])) {
            $paths['extend'] = $paths['base'] . "/extend.php";
        }

        date_default_timezone_set('UTC');

        if (static::hasConfigFile($paths['config'])) {
            return (
                new InstalledSite($paths, static::loadConfig($paths['config']))
            )->extendWith(static::loadExtenders($paths['extend']));
        } else {
            return new UninstalledSite($paths);
        }
    }

    private static function hasConfigFile($configPath)
    {
        return file_exists($configPath);
    }

    private static function loadConfig($configPath): array
    {
        $config = include $configPath;

        if (! is_array($config)) {
            throw new RuntimeException('config.php should return an array');
        }

        return $config;
    }

    private static function loadExtenders($extenderFile): array
    {

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
