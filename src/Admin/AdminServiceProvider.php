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

use Flarum\Extension\Event\Disabled;
use Flarum\Extension\Event\Enabled;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Http\RouteCollection;
use Flarum\Http\RouteHandlerFactory;
use Flarum\Settings\Event\Saved;

class AdminServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->app->singleton(UrlGenerator::class, function () {
            return new UrlGenerator($this->app, $this->app->make('flarum.admin.routes'));
        });

        $this->app->singleton('flarum.admin.routes', function () {
            return new RouteCollection;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $this->populateRoutes($this->app->make('flarum.admin.routes'));

        $this->loadViewsFrom(__DIR__.'/../../views', 'flarum.admin');

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
