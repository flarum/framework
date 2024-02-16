<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api;

use Flarum\Api\Controller\AbstractSerializeController;
use Flarum\Api\Serializer\AbstractSerializer;
use Flarum\Api\Serializer\BasicDiscussionSerializer;
use Flarum\Api\Serializer\NotificationSerializer;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Foundation\ErrorHandling\JsonApiFormatter;
use Flarum\Foundation\ErrorHandling\Registry;
use Flarum\Foundation\ErrorHandling\Reporter;
use Flarum\Http\Middleware as HttpMiddleware;
use Flarum\Http\RouteCollection;
use Flarum\Http\RouteHandlerFactory;
use Flarum\Http\UrlGenerator;
use Illuminate\Contracts\Container\Container;
use Laminas\Stratigility\MiddlewarePipe;

class ApiServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        $this->container->extend(UrlGenerator::class, function (UrlGenerator $url, Container $container) {
            return $url->addCollection('api', $container->make('flarum.api.routes'), 'api');
        });

        $this->container->singleton('flarum.api.resources', function () {
            return [
                Resource\ForumResource::class,
                Resource\UserResource::class,
                Resource\GroupResource::class,
                Resource\PostResource::class,
                Resource\DiscussionResource::class,
                Resource\NotificationResource::class,
            ];
        });

        $this->container->singleton('flarum.api.resource_handler', function (Container $container) {
            $resources = $this->container->make('flarum.api.resources');

            $api = new JsonApi('/');

            foreach ($resources as $resourceClass) {
                /** @var \Flarum\Api\Resource\AbstractResource|\Flarum\Api\Resource\AbstractDatabaseResource $resource */
                $resource = new $resourceClass;
                $resource->boot($container);
                $api->resource($resource);
            }

            return $api;
        });

        $this->container->alias('flarum.api.resource_handler', JsonApi::class);

        $this->container->singleton('flarum.api.routes', function () {
            $routes = new RouteCollection;
            $this->populateRoutes($routes);

            return $routes;
        });

        $this->container->singleton('flarum.api.throttlers', function () {
            return [
                'bypassThrottlingAttribute' => function ($request) {
                    if ($request->getAttribute('bypassThrottling')) {
                        return false;
                    }
                }
            ];
        });

        $this->container->bind(Middleware\ThrottleApi::class, function (Container $container) {
            return new Middleware\ThrottleApi($container->make('flarum.api.throttlers'));
        });

        $this->container->singleton('flarum.api.middleware', function () {
            return [
                HttpMiddleware\InjectActorReference::class,
                'flarum.api.error_handler',
                HttpMiddleware\ParseJsonBody::class,
                Middleware\FakeHttpMethods::class,
                HttpMiddleware\StartSession::class,
                HttpMiddleware\RememberFromCookie::class,
                HttpMiddleware\AuthenticateWithSession::class,
                HttpMiddleware\AuthenticateWithHeader::class,
                HttpMiddleware\SetLocale::class,
                'flarum.api.route_resolver',
                HttpMiddleware\CheckCsrfToken::class,
                Middleware\ThrottleApi::class,
                HttpMiddleware\PopulateWithActor::class,
            ];
        });

        $this->container->bind('flarum.api.error_handler', function (Container $container) {
            return new HttpMiddleware\HandleErrors(
                $container->make(Registry::class),
                new JsonApiFormatter($container['flarum.config']->inDebugMode()),
                $container->tagged(Reporter::class)
            );
        });

        $this->container->bind('flarum.api.route_resolver', function (Container $container) {
            return new HttpMiddleware\ResolveRoute($container->make('flarum.api.routes'));
        });

        $this->container->singleton('flarum.api.handler', function (Container $container) {
            $pipe = new MiddlewarePipe;

            foreach ($this->container->make('flarum.api.middleware') as $middleware) {
                $pipe->pipe($container->make($middleware));
            }

            $pipe->pipe(new HttpMiddleware\ExecuteRoute());

            return $pipe;
        });

        $this->container->singleton('flarum.api_client.exclude_middleware', function () {
            return [
                HttpMiddleware\InjectActorReference::class,
                HttpMiddleware\ParseJsonBody::class,
                Middleware\FakeHttpMethods::class,
                HttpMiddleware\StartSession::class,
                HttpMiddleware\AuthenticateWithSession::class,
                HttpMiddleware\AuthenticateWithHeader::class,
                HttpMiddleware\CheckCsrfToken::class,
                HttpMiddleware\RememberFromCookie::class,
            ];
        });

        $this->container->singleton(Client::class, function ($container) {
            $exclude = $container->make('flarum.api_client.exclude_middleware');

            $middlewareStack = array_filter($container->make('flarum.api.middleware'), function ($middlewareClass) use ($exclude) {
                return ! in_array($middlewareClass, $exclude);
            });

            $middlewareStack[] = HttpMiddleware\ExecuteRoute::class;

            return new Client(
                new ClientMiddlewarePipe($container, $middlewareStack)
            );
        });
    }

    public function boot(Container $container): void
    {
        AbstractSerializeController::setContainer($container);
        AbstractSerializer::setContainer($container);
    }

    protected function populateRoutes(RouteCollection $routes): void
    {
        /** @var RouteHandlerFactory $factory */
        $factory = $this->container->make(RouteHandlerFactory::class);

        $callback = include __DIR__.'/routes.php';
        $callback($routes, $factory);

        $resources = $this->container->make('flarum.api.resources');

        foreach ($resources as $resourceClass) {
            /** @var \Flarum\Api\Resource\AbstractResource|\Flarum\Api\Resource\AbstractDatabaseResource $resource */
            $resource = new $resourceClass;
            /** @var \Flarum\Api\Endpoint\Endpoint[] $endpoints */
            $endpoints = $resource->endpoints();
            $type = $resource->type();

            foreach ($endpoints as $endpoint) {
                $route = $endpoint->route();

                $routes->addRoute($route->method, rtrim("/$type$route->path", '/'), "$type.$route->name", $factory->toApiResource($resourceClass, $endpoint::class));
            }
        }
    }
}
