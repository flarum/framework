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
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/../../views/install', 'flarum.install');

        $this->populateRoutes($this->container->make('flarum.install.routes'));
    }

    /**
     * @param RouteCollection $routes
     */
    protected function populateRoutes(RouteCollection $routes)
    {
        $route = $this->container->make(RouteHandlerFactory::class);

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
