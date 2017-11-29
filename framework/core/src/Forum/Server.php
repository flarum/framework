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

use Exception;
use Flarum\Event\ConfigureMiddleware;
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
        $pipe->raiseThrowables();

        $path = parse_url($app->url(), PHP_URL_PATH);

        $pipe->pipe($path, $app->make('Flarum\Http\Middleware\HandleErrors', ['debug' => $app->inDebugMode() || ! $app->isInstalled()]));
        $pipe->pipe($path, $app->make('Flarum\Http\Middleware\StartSession'));

        if (! $app->isInstalled()) {
            $app->register('Flarum\Install\InstallServiceProvider');

            $pipe->pipe($path, $app->make('Flarum\Http\Middleware\DispatchRoute', ['routes' => $app->make('flarum.install.routes')]));
        } elseif ($app->isUpToDate() && ! $app->isDownForMaintenance()) {
            $pipe->pipe($path, $app->make('Flarum\Http\Middleware\ParseJsonBody'));
            $pipe->pipe($path, $app->make('Flarum\Http\Middleware\RememberFromCookie'));
            $pipe->pipe($path, $app->make('Flarum\Http\Middleware\AuthenticateWithSession'));
            $pipe->pipe($path, $app->make('Flarum\Http\Middleware\SetLocale'));
            $pipe->pipe($path, $app->make('Flarum\Http\Middleware\ShareErrorsFromSession'));

            event(new ConfigureMiddleware($pipe, $path, $this));

            $pipe->pipe($path, $app->make('Flarum\Http\Middleware\DispatchRoute', ['routes' => $app->make('flarum.forum.routes')]));
        } else {
            $pipe->pipe($path, function () {
                throw new Exception('', 503);
            });
        }

        return $pipe;
    }
}
