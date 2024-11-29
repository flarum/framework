<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http;

use Closure;
use Flarum\Api\JsonApi;
use Flarum\Frontend\Controller as FrontendController;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;

/**
 * @internal
 */
class RouteHandlerFactory
{
    public function __construct(
        protected Container $container
    ) {
    }

    public function toController(callable|string $controller): Closure
    {
        return function (Request $request, array $routeParams) use ($controller) {
            $controller = $this->resolveController($controller);

            $request = $request->withQueryParams(array_merge($request->getQueryParams(), $routeParams));

            return $controller->handle($request);
        };
    }

    /**
     * @param class-string<\Tobyz\JsonApiServer\Resource\AbstractResource> $resourceClass
     */
    public function toApiResource(string $resourceClass, string $endpointName): Closure
    {
        return function (Request $request, array $routeParams) use ($resourceClass, $endpointName) {
            /** @var JsonApi $api */
            $api = $this->container->make(JsonApi::class);

            $api->validateQueryParameters($request);

            $request = $request->withQueryParams(array_merge($request->getQueryParams(), $routeParams));

            return $api->forResource($resourceClass)
                ->forEndpoint($endpointName)
                ->handle($request);
        };
    }

    public function toFrontend(string $frontend, callable|string|null $content = null): Closure
    {
        return $this->toController(function (Container $container) use ($frontend, $content) {
            $frontend = $container->make("flarum.frontend.$frontend", compact('content'));

            return new FrontendController($frontend);
        });
    }

    public function toForum(?string $content = null): Closure
    {
        return $this->toFrontend('forum', $content);
    }

    public function toAdmin(?string $content = null): Closure
    {
        return $this->toFrontend('admin', $content);
    }

    private function resolveController(callable|string $controller): Handler
    {
        $controller = is_callable($controller)
            ? $this->container->call($controller)
            : $this->container->make($controller);

        if (! $controller instanceof Handler) {
            throw new InvalidArgumentException('Controller must be an instance of '.Handler::class);
        }

        return $controller;
    }
}
