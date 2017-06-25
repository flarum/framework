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
use Flarum\Api\Serializer\NotificationSerializer;
use Flarum\Event\ConfigureApiRoutes;
use Flarum\Event\ConfigureNotificationTypes;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Http\RouteCollection;
use Flarum\Http\RouteHandlerFactory;
use Flarum\Http\UrlGenerator;
use Tobscure\JsonApi\ErrorHandler;
use Tobscure\JsonApi\Exception\Handler\FallbackExceptionHandler;
use Tobscure\JsonApi\Exception\Handler\InvalidParameterExceptionHandler;

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
            $handler->registerHandler(new FallbackExceptionHandler($this->app->inDebugMode()));

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
            'discussionRenamed' => 'Flarum\Api\Serializer\DiscussionBasicSerializer'
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
