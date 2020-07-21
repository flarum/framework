<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Admin;

use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Foundation\ErrorHandling\Registry;
use Flarum\Foundation\ErrorHandling\Reporter;
use Flarum\Foundation\ErrorHandling\ViewFormatter;
use Flarum\Foundation\ErrorHandling\WhoopsFormatter;
use Flarum\Foundation\Event\ClearingCache;
use Flarum\Frontend\AddLocaleAssets;
use Flarum\Frontend\AddTranslations;
use Flarum\Frontend\Compiler\Source\SourceCollector;
use Flarum\Frontend\RecompileFrontendAssets;
use Flarum\Http\Middleware as HttpMiddleware;
use Flarum\Http\RouteCollection;
use Flarum\Http\RouteHandlerFactory;
use Flarum\Http\UrlGenerator;
use Flarum\Locale\LocaleManager;
use Flarum\Settings\Event\Saved;
use Illuminate\Support\Arr;
use Laminas\Stratigility\MiddlewarePipe;

class AdminServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->app->extend(UrlGenerator::class, function (UrlGenerator $url) {
            return $url->addCollection('admin', $this->app->make('flarum.admin.routes'), 'admin');
        });

        $this->app->singleton('flarum.admin.routes', function () {
            $routes = new RouteCollection;
            $this->populateRoutes($routes);

            return $routes;
        });

        $this->app->singleton('flarum.admin.middleware', function () {
            return [
                'flarum.admin.error_handler',
                'flarum.admin.proxy_middleware',
                HttpMiddleware\ParseJsonBody::class,
                HttpMiddleware\StartSession::class,
                HttpMiddleware\RememberFromCookie::class,
                HttpMiddleware\AuthenticateWithSession::class,
                HttpMiddleware\CheckCsrfToken::class,
                HttpMiddleware\SetLocale::class,
                Middleware\RequireAdministrateAbility::class,
            ];
        });

        $this->app->bind('flarum.admin.error_handler', function () {
            return new HttpMiddleware\HandleErrors(
                $this->app->make(Registry::class),
                $this->app['flarum']->inDebugMode() ? $this->app->make(WhoopsFormatter::class) : $this->app->make(ViewFormatter::class),
                $this->app->tagged(Reporter::class)
            );
        });

        $this->app->bind('flarum.admin.proxy_middleware', function () {
            $config = $this->app->get('flarum.config');

            return new HttpMiddleware\ProxyAddress(
                Arr::get($config, 'reverse_proxy.enabled', false),
                Arr::get($config, 'reverse_proxy.allowed', ['127.0.0.1'])
            );
        });

        $this->app->singleton('flarum.admin.handler', function () {
            $pipe = new MiddlewarePipe;

            foreach ($this->app->make('flarum.admin.middleware') as $middleware) {
                $pipe->pipe($this->app->make($middleware));
            }

            $pipe->pipe(new HttpMiddleware\DispatchRoute($this->app->make('flarum.admin.routes')));

            return $pipe;
        });

        $this->app->bind('flarum.assets.admin', function () {
            /** @var \Flarum\Frontend\Assets $assets */
            $assets = $this->app->make('flarum.assets.factory')('admin');

            $assets->js(function (SourceCollector $sources) {
                $sources->addFile(__DIR__.'/../../js/dist/admin.js');
            });

            $assets->css(function (SourceCollector $sources) {
                $sources->addFile(__DIR__.'/../../less/admin.less');
            });

            $this->app->make(AddTranslations::class)->forFrontend('admin')->to($assets);
            $this->app->make(AddLocaleAssets::class)->to($assets);

            return $assets;
        });

        $this->app->bind('flarum.frontend.admin', function () {
            /** @var \Flarum\Frontend\Frontend $frontend */
            $frontend = $this->app->make('flarum.frontend.factory')('admin');

            $frontend->content($this->app->make(Content\AdminPayload::class));

            return $frontend;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/../../views', 'flarum.admin');

        $events = $this->app->make('events');

        $events->listen(
            ClearingCache::class,
            function () {
                $recompile = new RecompileFrontendAssets(
                    $this->app->make('flarum.assets.admin'),
                    $this->app->make(LocaleManager::class)
                );
                $recompile->flush();
            }
        );

        $events->listen(
            Saved::class,
            function (Saved $event) {
                $recompile = new RecompileFrontendAssets(
                    $this->app->make('flarum.assets.admin'),
                    $this->app->make(LocaleManager::class)
                );
                $recompile->whenSettingsSaved($event);
            }
        );
    }

    /**
     * @param RouteCollection $routes
     */
    protected function populateRoutes(RouteCollection $routes)
    {
        $factory = $this->app->make(RouteHandlerFactory::class);

        $callback = include __DIR__.'/routes.php';
        $callback($routes, $factory);
    }
}
