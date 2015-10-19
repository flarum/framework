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
use Zend\Diactoros\Response\HtmlResponse;
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

        $basePath = parse_url($app->url(), PHP_URL_PATH);
        $errorDir = __DIR__.'/../../error';

        if (! $app->isInstalled()) {
            $app->register('Flarum\Install\InstallServiceProvider');

            $pipe->pipe($basePath, $app->make('Flarum\Http\Middleware\DispatchRoute', ['routes' => $app->make('flarum.install.routes')]));
            $pipe->pipe($basePath, new HandleErrors($errorDir, true));
        } elseif ($app->isUpToDate()) {
            $pipe->pipe($basePath, $app->make('Flarum\Http\Middleware\AuthenticateWithCookie'));
            $pipe->pipe($basePath, $app->make('Flarum\Http\Middleware\ParseJsonBody'));
            $pipe->pipe($basePath, $app->make('Flarum\Http\Middleware\DispatchRoute', ['routes' => $app->make('flarum.forum.routes')]));
            $pipe->pipe($basePath, new HandleErrors($errorDir, $app->inDebugMode()));
        } else {
            $pipe->pipe($basePath, function () use ($errorDir) {
                return new HtmlResponse(file_get_contents($errorDir.'/503.html', 503));
            });
        }

        return $pipe;
    }
}
