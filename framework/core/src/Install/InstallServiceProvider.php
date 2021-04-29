<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Install;

use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Http\RouteCollection;
use Flarum\Http\RouteHandlerFactory;
use Illuminate\Contracts\Container\Container;

class InstallServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->container->singleton('flarum.install.routes', function () {
            return new RouteCollection;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Container $container, RouteHandlerFactory $route)
    {
        $this->loadViewsFrom(__DIR__.'/../../views/install', 'flarum.install');

        $this->populateRoutes($container->make('flarum.install.routes'), $route);
    }

    /**
     * @param RouteCollection     $routes
     * @param RouteHandlerFactory $route
     */
    protected function populateRoutes(RouteCollection $routes, RouteHandlerFactory $route)
    {
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
