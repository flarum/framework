<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Update;

use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Http\GenerateRouteHandlerTrait;
use Flarum\Http\RouteCollection;

class UpdateServiceProvider extends AbstractServiceProvider
{
    use GenerateRouteHandlerTrait;

    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->app->singleton('flarum.update.routes', function () {
            return $this->getRoutes();
        });

        $this->loadViewsFrom(__DIR__.'/../../views/install', 'flarum.update');
    }

    /**
     * @return RouteCollection
     */
    protected function getRoutes()
    {
        $routes = new RouteCollection;

        $toController = $this->getHandlerGenerator($this->app);

        $routes->get(
            '/',
            'index',
            $toController('Flarum\Update\Controller\IndexController')
        );

        $routes->post(
            '/',
            'update',
            $toController('Flarum\Update\Controller\UpdateController')
        );

        return $routes;
    }
}
