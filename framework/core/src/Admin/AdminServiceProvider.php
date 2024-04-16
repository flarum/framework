<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Admin;

use Flarum\Extension\Event\Disabled;
use Flarum\Extension\Event\Enabled;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Foundation\ErrorHandling\Registry;
use Flarum\Foundation\ErrorHandling\Reporter;
use Flarum\Foundation\ErrorHandling\ViewFormatter;
use Flarum\Foundation\ErrorHandling\WhoopsFormatter;
use Flarum\Foundation\Event\ClearingCache;
use Flarum\Frontend\AddLocaleAssets;
use Flarum\Frontend\AddTranslations;
use Flarum\Frontend\AssetManager;
use Flarum\Frontend\Compiler\Source\SourceCollector;
use Flarum\Frontend\RecompileFrontendAssets;
use Flarum\Http\Middleware as HttpMiddleware;
use Flarum\Http\RouteCollection;
use Flarum\Http\RouteHandlerFactory;
use Flarum\Http\UrlGenerator;
use Flarum\Locale\LocaleManager;
use Flarum\Settings\Event\Saved;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Laminas\Stratigility\MiddlewarePipe;

class AdminServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        $this->container->extend(UrlGenerator::class, function (UrlGenerator $url, Container $container) {
            return $url->addCollection('admin', $container->make('flarum.admin.routes'), 'admin');
        });

        $this->container->singleton('flarum.admin.routes', function () {
            $routes = new RouteCollection;
            $this->populateRoutes($routes);

            return $routes;
        });

        $this->container->singleton('flarum.admin.middleware', function () {
            return [
                HttpMiddleware\InjectActorReference::class,
                'flarum.admin.error_handler',
                HttpMiddleware\ParseJsonBody::class,
                HttpMiddleware\StartSession::class,
                HttpMiddleware\RememberFromCookie::class,
                HttpMiddleware\AuthenticateWithSession::class,
                HttpMiddleware\SetLocale::class,
                'flarum.admin.route_resolver',
                HttpMiddleware\CheckCsrfToken::class,
                Middleware\RequireAdministrateAbility::class,
                HttpMiddleware\ReferrerPolicyHeader::class,
                HttpMiddleware\ContentTypeOptionsHeader::class,
                Middleware\DisableBrowserCache::class,
            ];
        });

        $this->container->bind('flarum.admin.error_handler', function (Container $container) {
            return new HttpMiddleware\HandleErrors(
                $container->make(Registry::class),
                $container['flarum.config']->inDebugMode() ? $container->make(WhoopsFormatter::class) : $container->make(ViewFormatter::class),
                $container->tagged(Reporter::class)
            );
        });

        $this->container->bind('flarum.admin.route_resolver', function (Container $container) {
            return new HttpMiddleware\ResolveRoute($container->make('flarum.admin.routes'));
        });

        $this->container->singleton('flarum.admin.handler', function (Container $container) {
            $pipe = new MiddlewarePipe;

            foreach ($container->make('flarum.admin.middleware') as $middleware) {
                $pipe->pipe($container->make($middleware));
            }

            $pipe->pipe(new HttpMiddleware\ExecuteRoute());

            return $pipe;
        });

        $this->container->bind('flarum.assets.admin', function (Container $container) {
            /** @var \Flarum\Frontend\Assets $assets */
            $assets = $container->make('flarum.assets.factory')('admin');

            $assets->js(function (SourceCollector $sources) {
                $sources->addFile(__DIR__.'/../../js/dist/admin.js');
            });

            $assets->css(function (SourceCollector $sources) {
                $sources->addFile(__DIR__.'/../../less/admin.less');
            });

            $container->make(AddTranslations::class)->forFrontend('admin')->to($assets);
            $container->make(AddLocaleAssets::class)->to($assets);

            return $assets;
        });

        $this->container->afterResolving(AssetManager::class, function (AssetManager $assets) {
            $assets->register('admin', 'flarum.assets.admin');
        });

        $this->container->bind('flarum.frontend.admin', function (Container $container) {
            /** @var \Flarum\Frontend\Frontend $frontend */
            $frontend = $container->make('flarum.frontend.factory')('admin');

            $frontend->content($container->make(Content\AdminPayload::class), 100);

            return $frontend;
        });
    }

    public function boot(Container $container, Dispatcher $events): void
    {
        $this->loadViewsFrom(__DIR__.'/../../views', 'flarum.admin');

        $events->listen(
            [Enabled::class, Disabled::class, ClearingCache::class],
            function () use ($container) {
                $recompile = new RecompileFrontendAssets(
                    $container->make('flarum.assets.admin'),
                    $container->make(LocaleManager::class)
                );
                $recompile->flush();
            }
        );

        $events->listen(
            Saved::class,
            function (Saved $event) use ($container) {
                /** @var WhenSavingSettings $listener */
                $listener = $container->make(WhenSavingSettings::class);

                $listener->afterSave($event);
            }
        );
    }

    protected function populateRoutes(RouteCollection $routes): void
    {
        $factory = $this->container->make(RouteHandlerFactory::class);

        $callback = include __DIR__.'/routes.php';
        $callback($routes, $factory);
    }
}
