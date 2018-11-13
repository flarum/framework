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
use Flarum\Http\RouteCollection;
use Flarum\Http\RouteHandlerFactory;
use Flarum\Install\Prerequisite\Composite;
use Flarum\Install\Prerequisite\PhpExtensions;
use Flarum\Install\Prerequisite\PhpVersion;
use Flarum\Install\Prerequisite\PrerequisiteInterface;
use Flarum\Install\Prerequisite\WritablePaths;

class InstallServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->app->bind(
            PrerequisiteInterface::class,
            function () {
                return new Composite(
                    new PhpVersion('7.1.0'),
                    new PhpExtensions([
                        'dom',
                        'gd',
                        'json',
                        'mbstring',
                        'openssl',
                        'pdo_mysql',
                        'tokenizer',
                    ]),
                    new WritablePaths([
                        base_path(),
                        public_path('assets'),
                        storage_path(),
                    ])
                );
            }
        );

        $this->app->singleton('flarum.install.routes', function () {
            return new RouteCollection;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/../../views/install', 'flarum.install');

        $this->populateRoutes($this->app->make('flarum.install.routes'));
    }

    /**
     * @param RouteCollection $routes
     */
    protected function populateRoutes(RouteCollection $routes)
    {
        $route = $this->app->make(RouteHandlerFactory::class);

        $routes->get(
            '/{path:.*}',
            'index',
            $route->toController(Controller\IndexController::class)
        );

        $routes->post(
            '/{path:.*}',
            'install',
            $route->toController(Controller\InstallController::class)
        );
    }
}
