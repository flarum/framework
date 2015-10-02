<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Server;

use Zend\Diactoros\Server as HttpServer;
use Flarum\Core;
use Flarum\Core\Application;
use Zend\Stratigility\MiddlewarePipe;
use Flarum\Api\Middleware\JsonApiErrors;
use Flarum\Forum\Middleware\HandleErrors;
use Franzl\Middleware\Whoops\Middleware as WhoopsMiddleware;

class WebServer extends ServerAbstract
{
    /**
     * @return void
     */
    public function listen()
    {
        $app = $this->getApp();

        $server = HttpServer::createServer(
            $this->getMiddlewarePipe($app),
            $_SERVER,
            $_GET,
            $_POST,
            $_COOKIE,
            $_FILES
        );

        $server->listen();
    }

    /**
     * @param \Flarum\Core\Application
     *
     * @return \Zend\Stratigility\MiddlewarePipe
     */
    protected function getMiddlewarePipe(Application $app)
    {
        $flarum = new MiddlewarePipe();

        if (Core::isInstalled()) {
            $flarum->pipe($app->make('Flarum\Forum\Middleware\LoginWithCookie'));
            $flarum->pipe($app->make('Flarum\Api\Middleware\ReadJsonParameters'));

            // API
            $app->register('Flarum\Api\ApiServiceProvider');
            $apiPath = parse_url(Core::url('api'), PHP_URL_PATH);
            $router = $app->make('Flarum\Http\RouterMiddleware', ['routes' => $app->make('flarum.api.routes')]);

            $flarum->pipe($apiPath, $app->make('Flarum\Api\Middleware\LoginWithHeader'));
            $flarum->pipe($apiPath, $app->make('Flarum\Api\Middleware\FakeHttpMethods'));
            $flarum->pipe($apiPath, $router);

            // Admin
            $app->register('Flarum\Admin\AdminServiceProvider');
            $adminPath = parse_url(Core::url('admin'), PHP_URL_PATH);
            $router = $app->make('Flarum\Http\RouterMiddleware', ['routes' => $app->make('flarum.admin.routes')]);

            $flarum->pipe($adminPath, $router);

            // Forum
            $app->register('Flarum\Forum\ForumServiceProvider');
            $basePath = parse_url(Core::url(), PHP_URL_PATH);
            $router = $app->make('Flarum\Http\RouterMiddleware', ['routes' => $app->make('flarum.forum.routes')]);

            $flarum->pipe($basePath, $router);

            if (Core::inDebugMode()) {
                $flarum->pipe(new WhoopsMiddleware());
            } else {
                $flarum->pipe(new HandleErrors(__DIR__.'/../../error'));
            }
        } else {
            $app->register('Flarum\Install\InstallServiceProvider');

            $basePath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $router = $app->make('Flarum\Http\RouterMiddleware', ['routes' => $app->make('flarum.install.routes')]);
            $flarum->pipe($basePath, $router);
            $flarum->pipe(new WhoopsMiddleware());
        }

        return $flarum;
    }
}
