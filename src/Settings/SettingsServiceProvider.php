<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Settings;

use Flarum\Api\Controller\DeleteFaviconController;
use Flarum\Api\Controller\DeleteLogoController;
use Flarum\Api\Controller\UploadFaviconController;
use Flarum\Api\Controller\UploadLogoController;
use Flarum\Foundation\AbstractServiceProvider;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Database\ConnectionInterface;
use League\Flysystem\FilesystemInterface;

class SettingsServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->app->singleton(SettingsRepositoryInterface::class, function () {
            return new MemoryCacheSettingsRepository(
                new DatabaseSettingsRepository(
                    $this->app->make(ConnectionInterface::class)
                )
            );
        });

        $this->app->alias(SettingsRepositoryInterface::class, 'flarum.settings');

        $assets = function (Container $app) {
            return $app->make(Factory::class)->disk('flarum-assets')->getDriver();
        };

        $this->app->when([
            DeleteFaviconController::class,
            DeleteLogoController::class,
            UploadFaviconController::class,
            UploadLogoController::class,
        ])
            ->needs(FilesystemInterface::class)
            ->give($assets);
    }
}
