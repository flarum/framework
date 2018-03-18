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
use Flarum\Extension\Event\Disabled;
use Flarum\Extension\Event\Enabled;
use Flarum\Foundation\AbstractServiceProvider;
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
use Flarum\Settings\Event\Saved;
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
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $this->populateRoutes($this->app->make('flarum.admin.routes'));

        $this->loadViewsFrom(__DIR__.'/../../views', 'flarum.admin');

        $this->registerListeners();
    }

    /**
     * Populate the forum client routes.
     *
     * @param RouteCollection $routes
     */
    protected function populateRoutes(RouteCollection $routes)
    {
        $factory = $this->app->make(RouteHandlerFactory::class);

        $callback = include __DIR__.'/routes.php';
        $callback($routes, $factory);
    }

    protected function registerListeners()
    {
        $dispatcher = $this->app->make('events');

        // Flush web app assets when the theme is changed
        $dispatcher->listen(Saved::class, function (Saved $event) {
            if (preg_match('/^theme_|^custom_less$/i', $event->key)) {
                $this->getWebAppAssets()->flushCss();
            }
        });

        // Flush web app assets when extensions are changed
        $dispatcher->listen(Enabled::class, [$this, 'flushWebAppAssets']);
        $dispatcher->listen(Disabled::class, [$this, 'flushWebAppAssets']);

        // Check the format of custom LESS code
        $dispatcher->subscribe(CheckCustomLessFormat::class);
    }

    public function flushWebAppAssets()
    {
        $this->getWebAppAssets()->flush();
    }

    /**
     * @return \Flarum\Frontend\FrontendAssets
     */
    protected function getWebAppAssets()
    {
        return $this->app->make(Frontend::class)->getAssets();
    }
}
