<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum;

use Flarum\Event\ConfigureForumRoutes;
use Flarum\Event\ConfigureMiddleware;
use Flarum\Extension\Event\Disabled;
use Flarum\Extension\Event\Enabled;
use Flarum\Formatter\Formatter;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Foundation\Application;
use Flarum\Foundation\ErrorHandling\Registry;
use Flarum\Foundation\ErrorHandling\Reporter;
use Flarum\Foundation\ErrorHandling\ViewFormatter;
use Flarum\Foundation\ErrorHandling\WhoopsFormatter;
use Flarum\Foundation\Event\ClearingCache;
use Flarum\Frontend\AddLocaleAssets;
use Flarum\Frontend\AddTranslations;
use Flarum\Frontend\Assets;
use Flarum\Frontend\Compiler\Source\SourceCollector;
use Flarum\Frontend\RecompileFrontendAssets;
use Flarum\Http\Middleware as HttpMiddleware;
use Flarum\Http\RouteCollection;
use Flarum\Http\RouteHandlerFactory;
use Flarum\Http\UrlGenerator;
use Flarum\Locale\LocaleManager;
use Flarum\Settings\Event\Saved;
use Flarum\Settings\Event\Saving;
use Flarum\Settings\SettingsRepositoryInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Zend\Stratigility\MiddlewarePipe;

class ForumServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->app->extend(UrlGenerator::class, function (UrlGenerator $url) {
            return $url->addCollection('forum', $this->app->make('flarum.forum.routes'));
        });

        $this->app->singleton('flarum.forum.routes', function () {
            $routes = new RouteCollection;
            $this->populateRoutes($routes);

            return $routes;
        });

        $this->app->afterResolving('flarum.forum.routes', function (RouteCollection $routes) {
            $this->setDefaultRoute($routes);
        });

        $this->app->singleton('flarum.forum.middleware', function (Application $app) {
            $pipe = new MiddlewarePipe;

            // All requests should first be piped through our global error handler
            $pipe->pipe(new HttpMiddleware\HandleErrors(
                $app->make(Registry::class),
                $app->inDebugMode() ? $app->make(WhoopsFormatter::class) : $app->make(ViewFormatter::class),
                $app->tagged(Reporter::class)
            ));

            $pipe->pipe($app->make(HttpMiddleware\ParseJsonBody::class));
            $pipe->pipe($app->make(HttpMiddleware\CollectGarbage::class));
            $pipe->pipe($app->make(HttpMiddleware\StartSession::class));
            $pipe->pipe($app->make(HttpMiddleware\RememberFromCookie::class));
            $pipe->pipe($app->make(HttpMiddleware\AuthenticateWithSession::class));
            $pipe->pipe($app->make(HttpMiddleware\CheckCsrfToken::class));
            $pipe->pipe($app->make(HttpMiddleware\SetLocale::class));
            $pipe->pipe($app->make(HttpMiddleware\ShareErrorsFromSession::class));

            event(new ConfigureMiddleware($pipe, 'forum'));

            return $pipe;
        });

        $this->app->afterResolving('flarum.forum.middleware', function (MiddlewarePipe $pipe) {
            $pipe->pipe(new HttpMiddleware\DispatchRoute($this->app->make('flarum.forum.routes')));
        });

        $this->app->bind('flarum.assets.forum', function () {
            /** @var Assets $assets */
            $assets = $this->app->make('flarum.assets.factory')('forum');

            $assets->js(function (SourceCollector $sources) {
                $sources->addFile(__DIR__.'/../../js/dist/forum.js');
                $sources->addString(function () {
                    return $this->app->make(Formatter::class)->getJs();
                });
            });

            $assets->css(function (SourceCollector $sources) {
                $sources->addFile(__DIR__.'/../../less/forum.less');
                $sources->addString(function () {
                    return $this->app->make(SettingsRepositoryInterface::class)->get('custom_less', '');
                });
            });

            $this->app->make(AddTranslations::class)->forFrontend('forum')->to($assets);
            $this->app->make(AddLocaleAssets::class)->to($assets);

            return $assets;
        });

        $this->app->bind('flarum.frontend.forum', function () {
            return $this->app->make('flarum.frontend.factory')('forum');
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/../../views', 'flarum.forum');

        $this->app->make('view')->share([
            'translator' => $this->app->make(TranslatorInterface::class),
            'settings' => $this->app->make(SettingsRepositoryInterface::class)
        ]);

        $events = $this->app->make('events');

        $events->listen(
            [Enabled::class, Disabled::class, ClearingCache::class],
            function () {
                $recompile = new RecompileFrontendAssets(
                    $this->app->make('flarum.assets.forum'),
                    $this->app->make(LocaleManager::class)
                );
                $recompile->flush();
            }
        );

        $events->listen(
            Saved::class,
            function (Saved $event) {
                $recompile = new RecompileFrontendAssets(
                    $this->app->make('flarum.assets.forum'),
                    $this->app->make(LocaleManager::class)
                );
                $recompile->whenSettingsSaved($event);

                $validator = new ValidateCustomLess(
                    $this->app->make('flarum.assets.forum'),
                    $this->app->make('flarum.locales'),
                    $this->app
                );
                $validator->whenSettingsSaved($event);
            }
        );

        $events->listen(
            Saving::class,
            function (Saving $event) {
                $validator = new ValidateCustomLess(
                    $this->app->make('flarum.assets.forum'),
                    $this->app->make('flarum.locales'),
                    $this->app
                );
                $validator->whenSettingsSaving($event);
            }
        );
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

        $this->app->make('events')->fire(
            new ConfigureForumRoutes($routes, $factory)
        );
    }

    /**
     * Determine the default route.
     *
     * @param RouteCollection $routes
     */
    protected function setDefaultRoute(RouteCollection $routes)
    {
        $factory = $this->app->make(RouteHandlerFactory::class);
        $defaultRoute = $this->app->make('flarum.settings')->get('default_route');

        if (isset($routes->getRouteData()[0]['GET'][$defaultRoute])) {
            $toDefaultController = $routes->getRouteData()[0]['GET'][$defaultRoute];
        } else {
            $toDefaultController = $factory->toForum(Content\Index::class);
        }

        $routes->get(
            '/',
            'default',
            $toDefaultController
        );
    }
}
