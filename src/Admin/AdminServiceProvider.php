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

use Flarum\Admin\Middleware\RequireAdministrateAbility;
use Flarum\Event\ConfigureMiddleware;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Frontend\RecompileFrontendAssets;
use Flarum\Http\Middleware\AuthenticateWithSession;
use Flarum\Http\Middleware\DispatchRoute;
use Flarum\Http\Middleware\HandleErrors;
use Flarum\Http\Middleware\ParseJsonBody;
use Flarum\Http\Middleware\RememberFromCookie;
use Flarum\Http\Middleware\SetLocale;
use Flarum\Http\Middleware\StartSession;
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

        $this->app->singleton('flarum.admin.middleware', function ($app) {
            $pipe = new MiddlewarePipe;

            // All requests should first be piped through our global error handler
            $debugMode = ! $app->isUpToDate() || $app->inDebugMode();
            $pipe->pipe($app->make(HandleErrors::class, ['debug' => $debugMode]));

            $pipe->pipe($app->make(ParseJsonBody::class));
            $pipe->pipe($app->make(StartSession::class));
            $pipe->pipe($app->make(RememberFromCookie::class));
            $pipe->pipe($app->make(AuthenticateWithSession::class));
            $pipe->pipe($app->make(SetLocale::class));
            $pipe->pipe($app->make(RequireAdministrateAbility::class));

            event(new ConfigureMiddleware($pipe, 'admin'));

            $pipe->pipe($app->make(DispatchRoute::class, ['routes' => $app->make('flarum.admin.routes')]));

            return $pipe;
        });

        $this->app->bind('flarum.admin.assets', function () {
            return $this->app->make('flarum.frontend.assets.defaults')('admin');
        });

        $this->app->bind('flarum.admin.frontend', function () {
            $view = $this->app->make('flarum.frontend.view.defaults')('admin');

            $view->setAssets($this->app->make('flarum.admin.assets'));
            $view->add($this->app->make(Content\AdminPayload::class));

            return $view;
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
                $this->app->make('flarum.admin.assets'),
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
