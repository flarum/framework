<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Install;

use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Http\Handler\RouteHandlerFactory;
use Flarum\Http\RouteCollection;
use Flarum\Install\Database\Drivers;
use Flarum\Install\Prerequisite\Composite;
use Flarum\Install\Prerequisite\PhpExtensions;
use Flarum\Install\Prerequisite\PhpVersion;
use Flarum\Install\Prerequisite\WritablePaths;

class InstallServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $drivers = new Drivers();

        $this->app->bind(
            'Flarum\Install\Prerequisite\PrerequisiteInterface',
            function () use ($drivers) {
                return new Composite(
                    new PhpVersion('5.5.0'),
                    new PhpExtensions([
                        'dom',
                        'fileinfo',
                        'gd',
                        'json',
                        'mbstring',
                        'openssl',
                        implode('|', $drivers->getSupportedPhpDrivers()),
                    ]),
                    new WritablePaths([
                        public_path(),
                        public_path('assets'),
                        storage_path(),
                    ])
                );
            }
        );

        $this->app->singleton('flarum.install.routes', function () {
            return new RouteCollection;
        });

        $this->app->singleton(
            'Flarum\Install\Database\Drivers',
            function () use ($drivers) {
                return $drivers;
        });

        $this->loadViewsFrom(__DIR__.'/../../views/install', 'flarum.install');
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $this->populateRoutes($this->app->make('flarum.install.routes'));
    }

    /**
     * @param RouteCollection $routes
     */
    protected function populateRoutes(RouteCollection $routes)
    {
        $route = $this->app->make(RouteHandlerFactory::class);

        $routes->get(
            '/',
            'index',
            $route->toController(Controller\IndexController::class)
        );

        $routes->post(
            '/',
            'install',
            $route->toController(Controller\InstallController::class)
        );
    }
}
