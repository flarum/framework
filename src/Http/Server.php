<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Http;

use Flarum\Foundation\AbstractServer;
use Flarum\Foundation\Application;
use Flarum\Http\Middleware\DispatchRoute;
use Flarum\Http\Middleware\HandleErrors;
use Flarum\Http\Middleware\StartSession;
use Flarum\Install\InstallServiceProvider;
use Flarum\Update\UpdateServiceProvider;
use Flarum\User\AuthToken;
use Flarum\User\EmailToken;
use Flarum\User\PasswordToken;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Server as DiactorosServer;
use Zend\Stratigility\MiddlewareInterface;
use Zend\Stratigility\MiddlewarePipe;
use Zend\Stratigility\NoopFinalHandler;

class Server extends AbstractServer
{
    public function listen()
    {
        DiactorosServer::createServer(
            $this,
            $_SERVER,
            $_GET,
            $_POST,
            $_COOKIE,
            $_FILES
        )->listen(new NoopFinalHandler());
    }

    /**
     * Use as PSR-7 middleware.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable $out
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $out)
    {
        $app = $this->getApp();

        $this->collectGarbage($app);

        $middleware = $this->getMiddleware($app, $request->getUri()->getPath());

        return $middleware($request, $response, $out);
    }

    /**
     * @param Application $app
     * @param string $requestPath
     * @return MiddlewareInterface
     */
    protected function getMiddleware(Application $app, $requestPath)
    {
        $pipe = new MiddlewarePipe;
        $pipe->raiseThrowables();

        if (! $app->isInstalled()) {
            return $this->getInstallerMiddleware($pipe, $app);
        } elseif ($app->isDownForMaintenance()) {
            return $this->getMaintenanceMiddleware($pipe);
        } elseif (! $app->isUpToDate()) {
            return $this->getUpdaterMiddleware($pipe, $app);
        }

        $forum = parse_url($app->url(''), PHP_URL_PATH) ?: '/';
        $admin = parse_url($app->url('admin'), PHP_URL_PATH);
        $api = parse_url($app->url('api'), PHP_URL_PATH);

        if ($this->pathStartsWith($requestPath, $api)) {
            $pipe->pipe($api, $app->make('flarum.api.middleware'));
        } elseif ($this->pathStartsWith($requestPath, $admin)) {
            $pipe->pipe($admin, $app->make('flarum.admin.middleware'));
        } else {
            $pipe->pipe($forum, $app->make('flarum.forum.middleware'));
        }

        return $pipe;
    }

    private function pathStartsWith($path, $prefix)
    {
        return $path === $prefix || starts_with($path, "$prefix/");
    }

    protected function getInstallerMiddleware(MiddlewarePipe $pipe, Application $app)
    {
        $app->register(InstallServiceProvider::class);

        $pipe->pipe(new HandleErrors($this->getErrorDir(), $app->make('log'), true));

        $pipe->pipe($app->make(StartSession::class));
        $pipe->pipe($app->make(DispatchRoute::class, ['routes' => $app->make('flarum.install.routes')]));

        return $pipe;
    }

    protected function getMaintenanceMiddleware(MiddlewarePipe $pipe)
    {
        $pipe->pipe(function () {
            return new HtmlResponse(file_get_contents($this->getErrorDir().'/503.html', 503));
        });

        // TODO: FOR API render JSON-API error document for HTTP 503

        return $pipe;
    }

    protected function getUpdaterMiddleware(MiddlewarePipe $pipe, Application $app)
    {
        $app->register(UpdateServiceProvider::class);

        $pipe->pipe($app->make(DispatchRoute::class, ['routes' => $app->make('flarum.update.routes')]));

        // TODO: FOR API render JSON-API error document for HTTP 503

        return $pipe;
    }

    private function collectGarbage()
    {
        if ($this->hitsLottery()) {
            AccessToken::whereRaw('last_activity <= ? - lifetime', [time()])->delete();

            $earliestToKeep = date('Y-m-d H:i:s', time() - 24 * 60 * 60);

            EmailToken::where('created_at', '<=', $earliestToKeep)->delete();
            PasswordToken::where('created_at', '<=', $earliestToKeep)->delete();
            AuthToken::where('created_at', '<=', $earliestToKeep)->delete();
        }
    }

    private function getErrorDir()
    {
        return __DIR__.'/../../error';
    }

    private function hitsLottery()
    {
        return mt_rand(1, 100) <= 2;
    }
}
