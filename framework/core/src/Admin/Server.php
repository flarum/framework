<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Admin;

use Flarum\Foundation\Application;
use Flarum\Http\AbstractServer;
use Zend\Stratigility\MiddlewarePipe;
use Flarum\Http\Middleware\HandleErrors;
use Franzl\Middleware\Whoops\Middleware as WhoopsMiddleware;

class Server extends AbstractServer
{
    /**
     * {@inheritdoc}
     */
    protected function getMiddleware(Application $app)
    {
        $pipe = new MiddlewarePipe;

        if ($app->isInstalled()) {
            $app->register('Flarum\Admin\AdminServiceProvider');

            $adminPath = parse_url($app->url('admin'), PHP_URL_PATH);
            $routes = $app->make('flarum.admin.routes');

            $pipe->pipe($adminPath, $app->make('Flarum\Http\Middleware\AuthenticateWithCookie'));
            $pipe->pipe($adminPath, $app->make('Flarum\Http\Middleware\ParseJsonBody'));
            $pipe->pipe($adminPath, $app->make('Flarum\Admin\Middleware\RequireAdministrateAbility'));
            $pipe->pipe($adminPath, $app->make('Flarum\Http\Middleware\DispatchRoute', compact('routes')));

            if ($app->inDebugMode()) {
                $pipe->pipe(new WhoopsMiddleware);
            } else {
                $pipe->pipe(new HandleErrors(__DIR__.'/../../error'));
            }
        }

        return $pipe;
    }
}
