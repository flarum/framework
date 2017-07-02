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

use Flarum\Foundation\Application;
use Flarum\Foundation\Site;
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

class Server
{
    /**
     * @param Site $site
     * @return Server
     */
    public static function fromSite(Site $site)
    {
        return new static($site->boot());
    }

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

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
        $this->collectGarbage();

        $middleware = $this->getMiddleware($request->getUri()->getPath());

        return $middleware($request, $response, $out);
    }

    /**
     * @param string $requestPath
     * @return MiddlewareInterface
     */
    protected function getMiddleware($requestPath)
    {
        $pipe = new MiddlewarePipe;
        $pipe->raiseThrowables();

        if (! $this->app->isInstalled()) {
            return $this->getInstallerMiddleware($pipe);
        }

        if ($this->app->isDownForMaintenance()) {
            return $this->getMaintenanceMiddleware($pipe);
        }

        if (! $this->app->isUpToDate()) {
            return $this->getUpdaterMiddleware($pipe);
        }

        $api = parse_url($this->app->url('api'), PHP_URL_PATH);
        $admin = parse_url($this->app->url('admin'), PHP_URL_PATH);
        $forum = parse_url($this->app->url(''), PHP_URL_PATH) ?: '/';

        if ($this->pathStartsWith($requestPath, $api)) {
            $pipe->pipe($api, $this->app->make('flarum.api.middleware'));
        } elseif ($this->pathStartsWith($requestPath, $admin)) {
            $pipe->pipe($admin, $this->app->make('flarum.admin.middleware'));
        } else {
            $pipe->pipe($forum, $this->app->make('flarum.forum.middleware'));
        }

        return $pipe;
    }

    private function pathStartsWith($path, $prefix)
    {
        return $path === $prefix || starts_with($path, "$prefix/");
    }

    protected function getInstallerMiddleware(MiddlewarePipe $pipe)
    {
        $this->app->register(InstallServiceProvider::class);

        $pipe->pipe(new HandleErrors($this->getErrorDir(), $this->app->make('log'), true));

        $pipe->pipe($this->app->make(StartSession::class));
        $pipe->pipe($this->app->make(DispatchRoute::class, ['routes' => $this->app->make('flarum.install.routes')]));

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

    protected function getUpdaterMiddleware(MiddlewarePipe $pipe)
    {
        $this->app->register(UpdateServiceProvider::class);

        $pipe->pipe($this->app->make(DispatchRoute::class, ['routes' => $this->app->make('flarum.update.routes')]));

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

    private function hitsLottery()
    {
        return mt_rand(1, 100) <= 2;
    }

    private function getErrorDir()
    {
        return __DIR__.'/../../error';
    }
}
