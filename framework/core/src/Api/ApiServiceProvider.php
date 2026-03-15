<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api;

use Flarum\Api\Endpoint\Endpoint;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Foundation\ErrorHandling\JsonApiFormatter;
use Flarum\Foundation\ErrorHandling\Registry;
use Flarum\Foundation\ErrorHandling\Reporter;
use Flarum\Foundation\MaintenanceMode;
use Flarum\Http\Middleware as HttpMiddleware;
use Flarum\Http\RouteCollection;
use Flarum\Http\RouteHandlerFactory;
use Flarum\Http\UrlGenerator;
use Illuminate\Contracts\Container\Container;
use Laminas\Stratigility\MiddlewarePipe;
use ReflectionClass;

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
                Resource\AccessTokenResource::class,
                Resource\MailSettingResource::class,
                Resource\ExtensionReadmeResource::class,
                Resource\SystemInfoResource::class,
            ];
        });

        $this->container->singleton('flarum.api.resource_handler', function (Container $container) {
            $resources = $this->container->make('flarum.api.resources');

            $api = new JsonApi('/');
            $api->container($container);

            foreach ($resources as $resourceClass) {
                /** @var \Flarum\Api\Resource\AbstractResource $resource */
                $resource = $container->make($resourceClass);
                $api->resource($resource->boot($api));
            }

            return $api;
        });

        $this->container->alias('flarum.api.resource_handler', JsonApi::class);

        $this->container->singleton('flarum.api.routes', function (Container $container) {
            $routes = new RouteCollection;
            $this->populateRoutes($routes, $container);

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
                'flarum.api.check_for_maintenance',
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

        $this->container->bind('flarum.api.check_for_maintenance', function (Container $container) {
            return new HttpMiddleware\CheckForMaintenanceMode(
                $container->make(MaintenanceMode::class),
                $container->make('flarum.api.maintenance_route_exclusions')
            );
        });

        $this->container->singleton('flarum.api.maintenance_route_exclusions', function () {
            return [];
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
                'flarum.api.check_for_maintenance',
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
        //
    }

    protected function populateRoutes(RouteCollection $routes, Container $container): void
    {
        /** @var RouteHandlerFactory $factory */
        $factory = $container->make(RouteHandlerFactory::class);

        $callback = include __DIR__.'/routes.php';
        $callback($routes, $factory);

        $resources = $this->container->make('flarum.api.resources');

        foreach ($resources as $resourceClass) {
            /**
             * This is an empty shell instance,
             * we only need it to get the endpoint routes and types.
             *
             * We avoid dependency injection here to avoid early resolution.
             *
             * @var \Flarum\Api\Resource\AbstractResource $resource
             */
            $resource = (new ReflectionClass($resourceClass))->newInstanceWithoutConstructor();

            $type = $resource->type();

            /**
             * None of the injected dependencies should be directly used within
             *   the `endpoints` method. Encourage using callbacks.
             *
             * @var array<Endpoint> $endpoints
             */
            $endpoints = $resource->resolveEndpoints(true);

            foreach ($endpoints as $endpoint) {
                $method = $endpoint->method;
                $path = rtrim("/$type$endpoint->path", '/');
                $name = "$type.$endpoint->name";

                if ($prefix = $resource->routeNamePrefix()) {
                    $name = "$prefix.$name";
                }

                $routes->addRoute($method, $path, $name, $factory->toApiResource($resource::class, $endpoint->name));
            }
        }
    }
}
