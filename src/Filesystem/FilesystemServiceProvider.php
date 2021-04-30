<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Filesystem;

use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Foundation\Paths;
use Flarum\Http\UrlGenerator;
use Illuminate\Contracts\Container\Container;
use Illuminate\Filesystem\Filesystem;

class FilesystemServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->container->singleton('files', function () {
            return new Filesystem;
        });

        $this->container->singleton('flarum.filesystem.disks', function () {
            return [
                'flarum-assets' => function (Paths $paths, UrlGenerator $url) {
                    return [
                        'root'   => "$paths->public/assets",
                        'url'    => $url->to('forum')->path('assets')
                    ];
                },
                'flarum-avatars' => function (Paths $paths, UrlGenerator $url) {
                    return [
                        'root'   => "$paths->public/assets/avatars",
                        'url'    => $url->to('forum')->path('assets/avatars')
                    ];
                },
            ];
        });

        $this->container->singleton('flarum.filesystem.drivers', function () {
            return [];
        });

        $this->container->singleton('flarum.filesystem.resolved_drivers', function (Container $container) {
            return array_map(function ($driverClass) use ($container) {
                return $container->make($driverClass);
            }, $container->make('flarum.filesystem.drivers'));
        });

        $this->container->singleton('filesystem', function (Container $container) {
            return new FilesystemManager(
                $container,
                $container->make('flarum.filesystem.disks'),
                $container->make('flarum.filesystem.resolved_drivers')
            );
        });
    }
}
