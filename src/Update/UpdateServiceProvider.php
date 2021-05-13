<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Update;

use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Http\RouteCollection;
use Flarum\Http\RouteHandlerFactory;
use Illuminate\Contracts\Container\Container;

class UpdateServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->container->singleton('flarum.update.routes', function (Container $container) {
            $routes = new RouteCollection;
            $factory = $container->make(RouteHandlerFactory::class);
            $this->populateRoutes($routes, $factory);

            return $routes;
        });
    }

    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/../../views/install', 'flarum.update');
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
            'update',
            $route->toController(Controller\UpdateController::class)
        );
    }
}
