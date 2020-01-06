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
use Flarum\Event\ConfigureApiRoutes;
use Flarum\Event\ConfigureMiddleware;
use Flarum\Event\ConfigureNotificationTypes;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Foundation\Application;
use Flarum\Foundation\ErrorHandling\JsonApiFormatter;
use Flarum\Foundation\ErrorHandling\Registry;
use Flarum\Foundation\ErrorHandling\Reporter;
use Flarum\Http\Middleware as HttpMiddleware;
use Flarum\Http\RouteCollection;
use Flarum\Http\RouteHandlerFactory;
use Flarum\Http\UrlGenerator;
use Laminas\Stratigility\MiddlewarePipe;

class ApiServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->app->extend(UrlGenerator::class, function (UrlGenerator $url) {
            return $url->addCollection('api', $this->app->make('flarum.api.routes'), 'api');
        });

        $this->app->singleton('flarum.api.routes', function () {
            $routes = new RouteCollection;
            $this->populateRoutes($routes);

            return $routes;
        });

        $this->app->singleton('flarum.api.middleware', function (Application $app) {
            $pipe = new MiddlewarePipe;

            $pipe->pipe(new HttpMiddleware\HandleErrors(
                $app->make(Registry::class),
                new JsonApiFormatter($app->inDebugMode()),
                $app->tagged(Reporter::class)
            ));

            $pipe->pipe($app->make(HttpMiddleware\ParseJsonBody::class));
            $pipe->pipe($app->make(Middleware\FakeHttpMethods::class));
            $pipe->pipe($app->make(HttpMiddleware\StartSession::class));
            $pipe->pipe($app->make(HttpMiddleware\RememberFromCookie::class));
            $pipe->pipe($app->make(HttpMiddleware\AuthenticateWithSession::class));
            $pipe->pipe($app->make(HttpMiddleware\AuthenticateWithHeader::class));
            $pipe->pipe($app->make(HttpMiddleware\CheckCsrfToken::class));
            $pipe->pipe($app->make(HttpMiddleware\SetLocale::class));

            event(new ConfigureMiddleware($pipe, 'api'));

            return $pipe;
        });

        $this->app->afterResolving('flarum.api.middleware', function (MiddlewarePipe $pipe) {
            $pipe->pipe(new HttpMiddleware\DispatchRoute($this->app->make('flarum.api.routes')));
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $this->registerNotificationSerializers();

        AbstractSerializeController::setContainer($this->app);
        AbstractSerializeController::setEventDispatcher($events = $this->app->make('events'));

        AbstractSerializer::setContainer($this->app);
        AbstractSerializer::setEventDispatcher($events);
    }

    /**
     * Register notification serializers.
     */
    protected function registerNotificationSerializers()
    {
        $blueprints = [];
        $serializers = [
            'discussionRenamed' => BasicDiscussionSerializer::class
        ];

        $this->app->make('events')->fire(
            new ConfigureNotificationTypes($blueprints, $serializers)
        );

        foreach ($serializers as $type => $serializer) {
            NotificationSerializer::setSubjectSerializer($type, $serializer);
        }
    }

    /**
     * Populate the API routes.
     *
     * @param RouteCollection $routes
     */
    protected function populateRoutes(RouteCollection $routes)
    {
        $factory = $this->app->make(RouteHandlerFactory::class);

        $callback = include __DIR__.'/routes.php';
        $callback($routes, $factory);

        $this->app->make('events')->fire(
            new ConfigureApiRoutes($routes, $factory)
        );
    }
}
