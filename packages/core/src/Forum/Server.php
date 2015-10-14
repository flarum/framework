<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Forum;

use Flarum\Foundation\Application;
use Flarum\Http\AbstractServer;
use Zend\Stratigility\MiddlewarePipe;
use Flarum\Http\Middleware\HandleErrors;

class Server extends AbstractServer
{
    /**
     * {@inheritdoc}
     */
    protected function getMiddleware(Application $app)
    {
        $pipe = new MiddlewarePipe;

        $installed = $app->isInstalled();
        $basePath = parse_url($app->url(), PHP_URL_PATH);

        if ($installed) {
            $app->register('Flarum\Forum\ForumServiceProvider');

            $routes = $app->make('flarum.forum.routes');

            $pipe->pipe($basePath, $app->make('Flarum\Http\Middleware\AuthenticateWithCookie'));
            $pipe->pipe($basePath, $app->make('Flarum\Http\Middleware\ParseJsonBody'));
        } else {
            $app->register('Flarum\Install\InstallServiceProvider');

            $routes = $app->make('flarum.install.routes');
        }

        $pipe->pipe($basePath, $app->make('Flarum\Http\Middleware\DispatchRoute', compact('routes')));

        $pipe->pipe(new HandleErrors(__DIR__.'/../../error', $app->inDebugMode() || ! $installed));

        return $pipe;
    }
}
