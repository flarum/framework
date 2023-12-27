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
use Intervention\Image\ImageManager;
use RuntimeException;

class ImageServiceProvider extends AbstractServiceProvider
{
    protected const INTERVENTION_DRIVERS = ['gd' => 'gd', 'imagick' => 'imagick'];

    public function register(): void
    {
        $this->container->singleton(ImageManager::class, function (Container $container) {
            /** @var Config $config */
            $config = $this->container->make(Config::class);

            $intervention = $config->offsetGet('intervention');
            $driver = Arr::get($intervention, 'driver', self::INTERVENTION_DRIVERS['gd']);

            // Check that the imagick library is actually available, else default back to gd.
            if ($driver === self::INTERVENTION_DRIVERS['imagick'] && ! extension_loaded(self::INTERVENTION_DRIVERS['imagick'])) {
                $driver = self::INTERVENTION_DRIVERS['gd'];
            }

            if (! Arr::has(self::INTERVENTION_DRIVERS, $driver)) {
                throw new RuntimeException("intervention/image: $driver is not valid");
            }

            return new ImageManager([
                'driver' => $driver
            ]);
        });
    }
}
