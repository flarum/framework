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

use Flarum\Event\ExtensionWasDisabled;
use Flarum\Event\ExtensionWasEnabled;
use Flarum\Event\SettingWasSet;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Http\GenerateRouteHandlerTrait;
use Flarum\Http\RouteCollection;

class AdminServiceProvider extends AbstractServiceProvider
{
    use GenerateRouteHandlerTrait;

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

        $this->flushAssetsWhenThemeChanged();

        $this->flushAssetsWhenExtensionsChanged();
    }

    /**
     * Populate the forum client routes.
     *
     * @param RouteCollection $routes
     */
    protected function populateRoutes(RouteCollection $routes)
    {
        $toController = $this->getHandlerGenerator($this->app);

        $routes->get(
            '/',
            'index',
            $toController('Flarum\Admin\Controller\ClientController')
        );
    }

    protected function flushAssetsWhenThemeChanged()
    {
        $this->app->make('events')->listen(SettingWasSet::class, function (SettingWasSet $event) {
            if (preg_match('/^theme_|^custom_less$/i', $event->key)) {
                $this->getClientController()->flushCss();
            }
        });
    }

    protected function flushAssetsWhenExtensionsChanged()
    {
        $events = $this->app->make('events');

        $events->listen(ExtensionWasEnabled::class, [$this, 'flushAssets']);
        $events->listen(ExtensionWasDisabled::class, [$this, 'flushAssets']);
    }

    public function flushAssets()
    {
        $this->getClientController()->flushAssets();
    }

    /**
     * @return \Flarum\Admin\Controller\ClientController
     */
    protected function getClientController()
    {
        return $this->app->make('Flarum\Admin\Controller\ClientController');
    }
}
