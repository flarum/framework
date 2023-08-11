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
use Flarum\Foundation\Config;
use Flarum\Foundation\ErrorHandling\JsonApiFormatter;
use Flarum\Foundation\ErrorHandling\Registry;
use Flarum\Foundation\ErrorHandling\Reporter;
use Flarum\Http\Middleware as HttpMiddleware;
use Flarum\Http\Router;
use Illuminate\Contracts\Container\Container;

class ApiServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        $this->booted(function (Container $container) {
            /** @var Router $router */
            $router = $container->make(Router::class);
            /** @var Config $config */
            $config = $container->make(Config::class);

            $router->middlewareGroup('api', $container->make('flarum.api.middleware'));

            $router->middleware('api')
                ->prefix($config->path('api'))
                ->name('api.')
                ->group(fn (Router $router) => (include __DIR__.'/routes.php')($router));
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
                Middleware\FakeHttpMethods::class,
                HttpMiddleware\StartSession::class,
                HttpMiddleware\RememberFromCookie::class,
                HttpMiddleware\AuthenticateWithSession::class,
                HttpMiddleware\AuthenticateWithHeader::class,
                HttpMiddleware\SetLocale::class,
                HttpMiddleware\CheckCsrfToken::class,
                Middleware\ThrottleApi::class
            ];
        });

        $this->container->bind('flarum.api.error_handler', function (Container $container) {
            return new HttpMiddleware\HandleErrors(
                $container->make(Registry::class),
                new JsonApiFormatter($container['flarum.config']->inDebugMode()),
                $container->tagged(Reporter::class)
            );
        });

        $this->container->singleton('flarum.api.notification_serializers', function () {
            return [
                'discussionRenamed' => BasicDiscussionSerializer::class
            ];
        });

        $this->container->singleton('flarum.api_client.exclude_middleware', function () {
            return [
                HttpMiddleware\InjectActorReference::class,
                Middleware\FakeHttpMethods::class,
                HttpMiddleware\StartSession::class,
                HttpMiddleware\AuthenticateWithSession::class,
                HttpMiddleware\AuthenticateWithHeader::class,
                HttpMiddleware\CheckCsrfToken::class,
                HttpMiddleware\RememberFromCookie::class,
            ];
        });

        $this->container->singleton(Client::class, function (Container $container) {
            $exclude = $container->make('flarum.api_client.exclude_middleware');

            $middlewareStack = array_filter($container->make('flarum.api.middleware'), function ($middlewareClass) use ($exclude) {
                return ! in_array($middlewareClass, $exclude);
            });

            return new Client($middlewareStack, $container);
        });
    }

    public function boot(Container $container): void
    {
        $this->setNotificationSerializers();

        AbstractSerializeController::setContainer($container);

        AbstractSerializer::setContainer($container);
    }

    protected function setNotificationSerializers(): void
    {
        $serializers = $this->container->make('flarum.api.notification_serializers');

        foreach ($serializers as $type => $serializer) {
            NotificationSerializer::setSubjectSerializer($type, $serializer);
        }
    }
}
