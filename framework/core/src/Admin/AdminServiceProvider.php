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
use Flarum\Foundation\Config;
use Flarum\Foundation\Event\ClearingCache;
use Flarum\Frontend\AddLocaleAssets;
use Flarum\Frontend\AddTranslations;
use Flarum\Frontend\Compiler\Source\SourceCollector;
use Flarum\Frontend\RecompileFrontendAssets;
use Flarum\Http\Middleware as HttpMiddleware;
use Flarum\Http\RouteHandlerFactory;
use Flarum\Http\Router;
use Flarum\Locale\LocaleManager;
use Flarum\Settings\Event\Saved;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;

class AdminServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        $this->container->singleton('flarum.admin.middleware', function () {
            return [
                HttpMiddleware\InjectActorReference::class,
                HttpMiddleware\StartSession::class,
                HttpMiddleware\RememberFromCookie::class,
                HttpMiddleware\AuthenticateWithSession::class,
                HttpMiddleware\SetLocale::class,
                HttpMiddleware\CheckCsrfToken::class,
                Middleware\RequireAdministrateAbility::class,
                HttpMiddleware\ReferrerPolicyHeader::class,
                HttpMiddleware\ContentTypeOptionsHeader::class,
                Middleware\DisableBrowserCache::class,
            ];
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

        $this->container->bind('flarum.frontend.admin', function (Container $container) {
            /** @var \Flarum\Frontend\Frontend $frontend */
            $frontend = $container->make('flarum.frontend.factory')('admin');

            $frontend->content($container->make(Content\AdminPayload::class));

            return $frontend;
        });
    }

    public function boot(Container $container, Dispatcher $events): void
    {
        $this->addRoutes($container);
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
                $recompile = new RecompileFrontendAssets(
                    $container->make('flarum.assets.admin'),
                    $container->make(LocaleManager::class)
                );
                $recompile->whenSettingsSaved($event);
            }
        );
    }

    protected function addRoutes(Container $container)
    {
        /** @var Router $router */
        $router = $container->make(Router::class);
        /** @var Config $config */
        $config = $container->make(Config::class);

        $router->middlewareGroup('admin', $container->make('flarum.admin.middleware'));

        $factory = $container->make(RouteHandlerFactory::class);

        $router->middleware('admin')
            ->prefix($config->path('admin'))
            ->name('admin.')
            ->group(fn (Router $router) => (include __DIR__.'/routes.php')($router, $factory));
    }
}
