<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api;

use Flarum\Foundation\Application;
use Flarum\Http\AbstractServer;
use Zend\Stratigility\MiddlewarePipe;

class Server extends AbstractServer
{
    /**
     * {@inheritdoc}
     */
    protected function getMiddleware(Application $app)
    {
        $pipe = new MiddlewarePipe;

        if ($app->isInstalled()) {
            $app->register('Flarum\Api\ApiServiceProvider');

            $routes = $app->make('flarum.api.routes');
            $apiPath = parse_url($app->url('api'), PHP_URL_PATH);

            $pipe->pipe($apiPath, $app->make('Flarum\Http\Middleware\AuthenticateWithCookie'));
            $pipe->pipe($apiPath, $app->make('Flarum\Api\Middleware\AuthenticateWithHeader'));
            $pipe->pipe($apiPath, $app->make('Flarum\Http\Middleware\ParseJsonBody'));
            $pipe->pipe($apiPath, $app->make('Flarum\Api\Middleware\FakeHttpMethods'));
            $pipe->pipe($apiPath, $app->make('Flarum\Http\Middleware\DispatchRoute', compact('routes')));

            $pipe->pipe($apiPath, $app->make('Flarum\Api\Middleware\HandleErrors'));
        }

        return $pipe;
    }
}
