<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Admin;

use Flarum\Event\ConfigureMiddleware;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Foundation\Application;
use Flarum\Frontend\AddLocaleAssets;
use Flarum\Frontend\AddTranslations;
use Flarum\Frontend\Compiler\Source\SourceCollector;
use Flarum\Frontend\RecompileFrontendAssets;
use Flarum\Http\Middleware as HttpMiddleware;
use Flarum\Http\RouteCollection;
use Flarum\Http\RouteHandlerFactory;
use Flarum\Http\UrlGenerator;
use Zend\Stratigility\MiddlewarePipe;

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
            return new RouteCollection;
        });

        $this->app->singleton('flarum.admin.middleware', function (Application $app) {
            $pipe = new MiddlewarePipe;

            // All requests should first be piped through our global error handler
            if ($app->inDebugMode()) {
                $pipe->pipe($app->make(HttpMiddleware\HandleErrorsWithWhoops::class));
            } else {
                $pipe->pipe($app->make(HttpMiddleware\HandleErrorsWithView::class));
            }

            $pipe->pipe($app->make(HttpMiddleware\ParseJsonBody::class));
            $pipe->pipe($app->make(HttpMiddleware\StartSession::class));
            $pipe->pipe($app->make(HttpMiddleware\RememberFromCookie::class));
            $pipe->pipe($app->make(HttpMiddleware\AuthenticateWithSession::class));
            $pipe->pipe($app->make(HttpMiddleware\SetLocale::class));
            $pipe->pipe($app->make(Middleware\RequireAdministrateAbility::class));

            event(new ConfigureMiddleware($pipe, 'admin'));

            return $pipe;
        });

        $this->app->afterResolving('flarum.admin.middleware', function (MiddlewarePipe $pipe) {
            $pipe->pipe(new HttpMiddleware\DispatchRoute($this->app->make('flarum.admin.routes')));
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
        $this->populateRoutes($this->app->make('flarum.admin.routes'));

        $this->loadViewsFrom(__DIR__.'/../../views', 'flarum.admin');

        $this->app->make('events')->subscribe(
            new RecompileFrontendAssets(
                $this->app->make('flarum.assets.admin'),
                $this->app->make('flarum.locales')
            )
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
