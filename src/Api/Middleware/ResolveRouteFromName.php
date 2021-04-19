<?php

namespace Flarum\Api\Middleware;

use Flarum\Http\RouteCollection;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ResolveRouteFromName implements MiddlewareInterface
{
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
}