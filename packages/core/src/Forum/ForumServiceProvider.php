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
use Flarum\Event\ExtensionWasDisabled;
use Flarum\Event\ExtensionWasEnabled;
use Flarum\Event\SettingWasSet;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Http\GenerateRouteHandlerTrait;
use Flarum\Http\RouteCollection;

class ForumServiceProvider extends AbstractServiceProvider
{
    use GenerateRouteHandlerTrait;

    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->app->singleton(UrlGenerator::class, function () {
            return new UrlGenerator($this->app, $this->app->make('flarum.forum.routes'));
        });

        $this->app->singleton('flarum.forum.routes', function () {
            return $this->getRoutes();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/../../views', 'flarum.forum');

        $this->flushAssetsWhenThemeChanged();

        $this->flushAssetsWhenExtensionsChanged();
    }

    /**
     * Get the forum client routes.
     *
     * @return RouteCollection
     */
    protected function getRoutes()
    {
        $routes = new RouteCollection;

        $toController = $this->getHandlerGenerator($this->app);

        $routes->get(
            '/all',
            'index',
            $toDefaultController = $toController('Flarum\Forum\Controller\IndexController')
        );

        $routes->get(
            '/d/{id:\d+(?:-[^/]*)?}[/{near:[^/]*}]',
            'discussion',
            $toController('Flarum\Forum\Controller\DiscussionController')
        );

        $routes->get(
            '/u/{username}[/{filter:[^/]*}]',
            'user',
            $toController('Flarum\Forum\Controller\ClientController')
        );

        $routes->get(
            '/settings',
            'settings',
            $toController('Flarum\Forum\Controller\ClientController')
        );

        $routes->get(
            '/notifications',
            'notifications',
            $toController('Flarum\Forum\Controller\ClientController')
        );

        $routes->get(
            '/logout',
            'logout',
            $toController('Flarum\Forum\Controller\LogoutController')
        );

        $routes->post(
            '/login',
            'login',
            $toController('Flarum\Forum\Controller\LoginController')
        );

        $routes->post(
            '/register',
            'register',
            $toController('Flarum\Forum\Controller\RegisterController')
        );

        $routes->get(
            '/confirm/{token}',
            'confirmEmail',
            $toController('Flarum\Forum\Controller\ConfirmEmailController')
        );

        $routes->get(
            '/reset/{token}',
            'resetPassword',
            $toController('Flarum\Forum\Controller\ResetPasswordController')
        );

        $routes->post(
            '/reset',
            'savePassword',
            $toController('Flarum\Forum\Controller\SavePasswordController')
        );

        $this->app->make('events')->fire(
            new ConfigureForumRoutes($routes, $toController)
        );

        $defaultRoute = $this->app->make('flarum.settings')->get('default_route');

        if (isset($routes->getRouteData()[0]['GET'][$defaultRoute])) {
            $toDefaultController = $routes->getRouteData()[0]['GET'][$defaultRoute];
        }

        $routes->get(
            '/',
            'default',
            $toDefaultController
        );

        return $routes;
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
     * @return \Flarum\Forum\Controller\ClientController
     */
    protected function getClientController()
    {
        return $this->app->make('Flarum\Forum\Controller\ClientController');
    }
}
