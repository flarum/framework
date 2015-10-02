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

use Flarum\Core\Users\Guest;
use Flarum\Events\RegisterForumRoutes;
use Flarum\Http\RouteCollection;
use Flarum\Forum\UrlGenerator;
use Flarum\Support\ServiceProvider;
use Psr\Http\Message\ServerRequestInterface;

class ForumServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('flarum.actor', function () {
            return new Guest;
        });

        $this->app->singleton(
            UrlGenerator::class,
            function () {
                return new UrlGenerator($this->app->make('flarum.forum.routes'));
            }
        );
    }

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $root = __DIR__.'/../..';

        $this->loadViewsFrom($root.'/views', 'flarum.forum');

        $this->publishes([
            $root.'/public/fonts' => public_path('assets/fonts')
        ]);

        $this->routes();
    }

    protected function routes()
    {
        $this->app->instance('flarum.forum.routes', $routes = new RouteCollection);

        $routes->get(
            '/all',
            'index',
            $defaultAction = $this->action('Flarum\Forum\Actions\IndexAction')
        );

        $routes->get(
            '/d/{id:\d+(?:-[^/]*)?}[/{near:[^/]*}]',
            'discussion',
            $this->action('Flarum\Forum\Actions\DiscussionAction')
        );

        $routes->get(
            '/u/{username}[/{filter:[^/]*}]',
            'user',
            $this->action('Flarum\Forum\Actions\ClientAction')
        );

        $routes->get(
            '/settings',
            'settings',
            $this->action('Flarum\Forum\Actions\ClientAction')
        );

        $routes->get(
            '/notifications',
            'notifications',
            $this->action('Flarum\Forum\Actions\ClientAction')
        );

        $routes->get(
            '/logout',
            'logout',
            $this->action('Flarum\Forum\Actions\LogoutAction')
        );

        $routes->post(
            '/login',
            'login',
            $this->action('Flarum\Forum\Actions\LoginAction')
        );

        $routes->post(
            '/register',
            'register',
            $this->action('Flarum\Forum\Actions\RegisterAction')
        );

        $routes->get(
            '/confirm/{token}',
            'confirmEmail',
            $this->action('Flarum\Forum\Actions\ConfirmEmailAction')
        );

        $routes->get(
            '/reset/{token}',
            'resetPassword',
            $this->action('Flarum\Forum\Actions\ResetPasswordAction')
        );

        $routes->post(
            '/reset',
            'savePassword',
            $this->action('Flarum\Forum\Actions\SavePasswordAction')
        );

        event(new RegisterForumRoutes($routes));

        $settings = $this->app->make('Flarum\Core\Settings\SettingsRepository');
        $defaultRoute = $settings->get('default_route');

        if (isset($routes->getRouteData()[0]['GET'][$defaultRoute])) {
            $defaultAction = $routes->getRouteData()[0]['GET'][$defaultRoute];
        }

        $routes->get(
            '/',
            'default',
            $defaultAction
        );
    }

    protected function action($class)
    {
        return function (ServerRequestInterface $httpRequest, $routeParams) use ($class) {
            /** @var \Flarum\Support\Action $action */
            $action = $this->app->make($class);

            return $action->handle($httpRequest, $routeParams);
        };
    }
}
