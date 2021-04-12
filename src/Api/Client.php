<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api;

use Exception;
use Flarum\Foundation\ErrorHandling\Registry;
use Flarum\Http\Middleware as HttpMiddleware;
use Flarum\Http\RequestUtil;
use Flarum\Http\RouteCollection;
use Flarum\User\User;
use Illuminate\Contracts\Container\Container;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Stratigility\MiddlewarePipe;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Client
{
    /**
     * @var MiddlewarePipe
     */
    protected $pipe;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @param Container $container
     * @param Registry $registry
     */
    public function __construct(Container $container, Registry $registry)
    {
        $this->registry = $registry;

        $middlewareStack = $container->make('flarum.api.middleware');

        $middlewareStack = array_filter($middlewareStack, function ($middlewareClass) {
            return ! in_array($middlewareClass, [
                HttpMiddleware\ParseJsonBody::class,
                HttpMiddleware\StartSession::class,
                HttpMiddleware\AuthenticateWithSession::class,
                'flarum.api.route_resolver',
                HttpMiddleware\CheckCsrfToken::class
            ]);
        });

        $routeCollection = $container->make('flarum.api.routes');

        $pipe = new MiddlewarePipe;
        $pipe->pipe(new class($routeCollection) implements MiddlewareInterface {
            public function __construct(RouteCollection $routeCollection)
            {
                $this->routeCollection = $routeCollection;
            }

            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                $route = $this->routeCollection->getRoutes()[$request->getAttribute('routeName')];

                $request = $request->withMethod($route['method']);
                $request = $request->withAttribute('routeHandler', $route['handler']);
                // These aren't available to be parsed out since we're passed
                // a route name, not a URL. If needed, these can be specified
                // as query params.
                $request = $request->withAttribute('routeParameters', []);

                return $handler->handle($request);
            }
        });

        foreach ($middlewareStack as $middleware) {
            $pipe->pipe($container->make($middleware));
        }

        $pipe->pipe(new HttpMiddleware\ExecuteRoute());

        $this->pipe = $pipe;
    }

    /**
     * Execute the given API action class, pass the input and return its response.
     *
     * @param string $routeName
     * @param User|null $actor
     * @param array $queryParams
     * @param array $body
     * @return ResponseInterface
     * @throws Exception
     */
    public function send(string $routeName, User $actor = null, array $queryParams = [], array $body = []): ResponseInterface
    {
        $request = ServerRequestFactory::fromGlobals(null, $queryParams, $body);
        $request = RequestUtil::withActor($request, $actor);
        $request = $request->withAttribute('routeName', $routeName);

        return $this->pipe->handle($request);
    }
}
