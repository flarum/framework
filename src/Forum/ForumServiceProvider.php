<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Forum;

use Flarum\Event\ConfigureForumRoutes;
use Flarum\Extension\Event\Disabled;
use Flarum\Extension\Event\Enabled;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Http\RouteCollection;
use Flarum\Http\RouteHandlerFactory;
use Flarum\Http\UrlGenerator;
use Flarum\Settings\Event\Saved;

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
            return new RouteCollection;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $this->populateRoutes($this->app->make('flarum.forum.routes'));

        $this->loadViewsFrom(__DIR__.'/../../views', 'flarum.forum');

        $this->flushWebAppAssetsWhenThemeChanged();

        $this->flushWebAppAssetsWhenExtensionsChanged();
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

        $defaultRoute = $this->app->make('flarum.settings')->get('default_route');

        if (isset($routes->getRouteData()[0]['GET'][$defaultRoute])) {
            $toDefaultController = $routes->getRouteData()[0]['GET'][$defaultRoute];
        } else {
            $toDefaultController = $factory->toController(Controller\IndexController::class);
        }

        $routes->get(
            '/',
            'default',
            $toDefaultController
        );
    }

    protected function flushWebAppAssetsWhenThemeChanged()
    {
        $this->app->make('events')->listen(Saved::class, function (Saved $event) {
            if (preg_match('/^theme_|^custom_less$/i', $event->key)) {
                $this->getWebAppAssets()->flushCss();
            }
        });
    }

    protected function flushWebAppAssetsWhenExtensionsChanged()
    {
        $events = $this->app->make('events');

        $events->listen(Enabled::class, [$this, 'flushWebAppAssets']);
        $events->listen(Disabled::class, [$this, 'flushWebAppAssets']);
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
