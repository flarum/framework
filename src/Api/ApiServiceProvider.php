<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
use Flarum\Http\Middleware as HttpMiddleware;
use Flarum\Http\RouteCollection;
use Flarum\Http\RouteHandlerFactory;
use Flarum\Http\UrlGenerator;
use Tobscure\JsonApi\ErrorHandler;
use Tobscure\JsonApi\Exception\Handler\InvalidParameterExceptionHandler;
use Zend\Stratigility\MiddlewarePipe;

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
            return new RouteCollection;
        });

        $this->app->singleton('flarum.api.middleware', function (Application $app) {
            $pipe = new MiddlewarePipe;

            $pipe->pipe($app->make(Middleware\HandleErrors::class));

            $pipe->pipe($app->make(HttpMiddleware\ParseJsonBody::class));
            $pipe->pipe($app->make(Middleware\FakeHttpMethods::class));
            $pipe->pipe($app->make(HttpMiddleware\StartSession::class));
            $pipe->pipe($app->make(HttpMiddleware\RememberFromCookie::class));
            $pipe->pipe($app->make(HttpMiddleware\AuthenticateWithSession::class));
            $pipe->pipe($app->make(HttpMiddleware\AuthenticateWithHeader::class));
            $pipe->pipe($app->make(HttpMiddleware\SetLocale::class));

            event(new ConfigureMiddleware($pipe, 'api'));

            return $pipe;
        });

        $this->app->afterResolving('flarum.api.middleware', function (MiddlewarePipe $pipe) {
            $pipe->pipe(new HttpMiddleware\DispatchRoute($this->app->make('flarum.api.routes')));
        });

        $this->app->singleton(ErrorHandler::class, function () {
            $handler = new ErrorHandler;

            $handler->registerHandler(new ExceptionHandler\FloodingExceptionHandler);
            $handler->registerHandler(new ExceptionHandler\IlluminateValidationExceptionHandler);
            $handler->registerHandler(new ExceptionHandler\InvalidAccessTokenExceptionHandler);
            $handler->registerHandler(new ExceptionHandler\InvalidConfirmationTokenExceptionHandler);
            $handler->registerHandler(new ExceptionHandler\MethodNotAllowedExceptionHandler);
            $handler->registerHandler(new ExceptionHandler\ModelNotFoundExceptionHandler);
            $handler->registerHandler(new ExceptionHandler\PermissionDeniedExceptionHandler);
            $handler->registerHandler(new ExceptionHandler\RouteNotFoundExceptionHandler);
            $handler->registerHandler(new ExceptionHandler\TokenMismatchExceptionHandler);
            $handler->registerHandler(new ExceptionHandler\ValidationExceptionHandler);
            $handler->registerHandler(new InvalidParameterExceptionHandler);
            $handler->registerHandler(new ExceptionHandler\FallbackExceptionHandler($this->app->inDebugMode(), $this->app->make('log')));

            return $handler;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $this->populateRoutes($this->app->make('flarum.api.routes'));

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
