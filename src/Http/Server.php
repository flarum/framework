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
use Flarum\Http\Response\FinalHandler;
use Flarum\Install\InstallServiceProvider;
use Flarum\Update\UpdateServiceProvider;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Server as DiactorosServer;
use Zend\Stratigility\MiddlewarePipe;
use function Zend\Stratigility\path;

class Server
{
    /**
     * @var Application
     */
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public static function fromSite(Site $site): Server
    {
        return new static($site->boot());
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
        )->listen(new FinalHandler());
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
        $middleware = $this->getMiddleware($request->getUri()->getPath());

        return $middleware->process($request, $response, $out);
    }

    protected function getMiddleware(string $requestPath): MiddlewarePipe
    {
        $pipe = new MiddlewarePipe;

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
            $pipe->pipe(path($api, $this->app->make('flarum.api.middleware')));
        } elseif ($this->pathStartsWith($requestPath, $admin)) {
            $pipe->pipe(path($admin, $this->app->make('flarum.admin.middleware')));
        } else {
            $pipe->pipe(path($forum, $this->app->make('flarum.forum.middleware')));
        }

        return $pipe;
    }

    private function pathStartsWith(string $path, string $prefix): bool
    {
        return $path === $prefix || starts_with($path, "$prefix/");
    }

    protected function getInstallerMiddleware(MiddlewarePipe $pipe): MiddlewarePipe
    {
        $this->app->register(InstallServiceProvider::class);

        // FIXME: Re-enable HandleErrors middleware, if possible
        // (Right now it tries to resolve a database connection because of the injected settings repo instance)
        // We could register a different settings repo when Flarum is not installed
        //$pipe->pipe($this->app->make(HandleErrors::class, ['debug' => true]));
        $pipe->pipe($this->app->make(StartSession::class));
        $pipe->pipe($this->app->make(DispatchRoute::class, ['routes' => $this->app->make('flarum.install.routes')]));

        return $pipe;
    }

    protected function getMaintenanceMiddleware(MiddlewarePipe $pipe): MiddlewarePipe
    {
        $pipe->pipe(function () {
            return new HtmlResponse(file_get_contents($this->getErrorDir().'/503.html', 503));
        });

        // TODO: FOR API render JSON-API error document for HTTP 503

        return $pipe;
    }

    protected function getUpdaterMiddleware(MiddlewarePipe $pipe): MiddlewarePipe
    {
        $this->app->register(UpdateServiceProvider::class);

        $pipe->pipe($this->app->make(DispatchRoute::class, ['routes' => $this->app->make('flarum.update.routes')]));

        // TODO: FOR API render JSON-API error document for HTTP 503

        return $pipe;
    }

    private function getErrorDir(): string
    {
        return __DIR__.'/../../error';
    }
}
