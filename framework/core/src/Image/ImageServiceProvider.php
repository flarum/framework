<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Image;

use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Foundation\Config;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;
use Intervention\Image\Drivers;
use Intervention\Image\ImageManager;
use RuntimeException;

class ImageServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        $this->container->bind('image.drivers', function (): array {
            return [
                'gd' => Drivers\Gd\Driver::class,
                'imagick' => Drivers\Imagick\Driver::class
            ];
        });

        $this->container->singleton('image', function (Container $container): ImageManager {
            $interventionDrivers = $container->make('image.drivers');

            $configDriver = $container->make(Config::class)->offsetGet('intervention.driver');

            // Default to 'gd' if not present in the config
            $driver = $configDriver ?? 'gd';

            // Check that the imagick library is actually available, else default back to gd.
            if ($driver === 'imagick' && ! extension_loaded('imagick')) {
                $driver = 'gd';
            }

            if (! Arr::has($interventionDrivers, $driver)) {
                throw new RuntimeException("intervention/image: $driver is not valid");
            }

            return new ImageManager($interventionDrivers[$driver]);
        });

        $this->container->alias('image', ImageManager::class);
    }
}
