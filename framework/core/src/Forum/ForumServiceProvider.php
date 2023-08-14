<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum;

use Flarum\Extension\Event\Disabled;
use Flarum\Extension\Event\Enabled;
use Flarum\Formatter\Formatter;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Foundation\Config;
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
use Flarum\Http\RouteHandlerFactory;
use Flarum\Http\Router;
use Flarum\Locale\LocaleManager;
use Flarum\Settings\Event\Saved;
use Flarum\Settings\Event\Saving;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Arr;
use Symfony\Contracts\Translation\TranslatorInterface;

class ForumServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        $this->container->singleton('flarum.forum.middleware', function () {
            return [
                HttpMiddleware\InjectActorReference::class,
                HttpMiddleware\CollectGarbage::class,
                HttpMiddleware\StartSession::class,
                HttpMiddleware\RememberFromCookie::class,
                HttpMiddleware\AuthenticateWithSession::class,
                HttpMiddleware\SetLocale::class,
                HttpMiddleware\CheckCsrfToken::class,
                HttpMiddleware\ShareErrorsFromSession::class,
                HttpMiddleware\FlarumPromotionHeader::class,
                HttpMiddleware\ReferrerPolicyHeader::class,
                HttpMiddleware\ContentTypeOptionsHeader::class
            ];
        });

        $this->container->bind('flarum.assets.forum', function (Container $container) {
            /** @var Assets $assets */
            $assets = $container->make('flarum.assets.factory')('forum');

            $assets->js(function (SourceCollector $sources) use ($container) {
                $sources->addFile(__DIR__.'/../../js/dist/forum.js');
                $sources->addString(function () use ($container) {
                    return $container->make(Formatter::class)->getJs();
                });
            });

            $assets->jsDirectory(function (SourceCollector $sources) {
                $sources->addDirectory(__DIR__.'/../../js/dist/forum', 'core');
            });

            $assets->css(function (SourceCollector $sources) use ($container) {
                $sources->addFile(__DIR__.'/../../less/forum.less');
                $sources->addString(function () use ($container) {
                    return $container->make(SettingsRepositoryInterface::class)->get('custom_less', '');
                });
            });

            $container->make(AddTranslations::class)->forFrontend('forum')->to($assets);
            $container->make(AddLocaleAssets::class)->to($assets);

            return $assets;
        });

        $this->container->bind('flarum.frontend.forum', function (Container $container) {
            return $container->make('flarum.frontend.factory')('forum');
        });

        $this->container->singleton('flarum.forum.discussions.sortmap', function () {
            return [
                'latest' => '-lastPostedAt',
                'top' => '-commentCount',
                'newest' => '-createdAt',
                'oldest' => 'createdAt'
            ];
        });
    }

    public function boot(Container $container, Dispatcher $events, Factory $view): void
    {
        $this->addRoutes($container);
        $this->loadViewsFrom(__DIR__.'/../../views', 'flarum.forum');

        $view->share([
            'translator' => $container->make(TranslatorInterface::class),
            'settings' => $container->make(SettingsRepositoryInterface::class)
        ]);

        $events->listen(
            [Enabled::class, Disabled::class, ClearingCache::class],
            function () use ($container) {
                $recompile = new RecompileFrontendAssets(
                    $container->make('flarum.assets.forum'),
                    $container->make(LocaleManager::class)
                );
                $recompile->flush();
            }
        );

        $events->listen(
            Saved::class,
            function (Saved $event) use ($container) {
                $recompile = new RecompileFrontendAssets(
                    $container->make('flarum.assets.forum'),
                    $container->make(LocaleManager::class)
                );
                $recompile->whenSettingsSaved($event);

                $validator = new ValidateCustomLess(
                    $container->make('flarum.assets.forum'),
                    $container->make('flarum.locales'),
                    $container,
                    $container->make('flarum.less.config')
                );
                $validator->whenSettingsSaved($event);
            }
        );

        $events->listen(
            Saving::class,
            function (Saving $event) use ($container) {
                $validator = new ValidateCustomLess(
                    $container->make('flarum.assets.forum'),
                    $container->make('flarum.locales'),
                    $container,
                    $container->make('flarum.less.config')
                );
                $validator->whenSettingsSaving($event);
            }
        );
    }

    protected function addRoutes(Container $container): void
    {
        /** @var Router $router */
        $router = $container->make(Router::class);
        /** @var Config $config */
        $config = $container->make(Config::class);

        $router->middlewareGroup('forum', $container->make('flarum.forum.middleware'));

        $factory = $container->make(RouteHandlerFactory::class);

        $router->middleware('forum')
            ->prefix($config->path('forum'))
            ->name('forum.')
            ->group(fn (Router $router) => (include __DIR__.'/routes.php')($router, $factory));

        $this->setDefaultRoute(
            $router,
            $container->make(SettingsRepositoryInterface::class)
        );
    }

    protected function setDefaultRoute(Router $router, SettingsRepositoryInterface $settings): void
    {
        $defaultRoutePath = ltrim($settings->get('default_route', '/all'), '/');
        /** @var \Illuminate\Routing\Route $route */
        $route = $router->getRoutes()->getRoutesByMethod()['GET'][$defaultRoutePath] ?? null;

        if (!$route) {
            $route = $router->getRoutes()->getRoutesByMethod()['GET']['all'];
        }

        $router->get('/', Arr::except($route->getAction(), ['as']))->name('forum.default');
    }
}
