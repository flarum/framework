<?php namespace Flarum\Forum;

use Flarum\Http\RouteCollection;
use Flarum\Http\UrlGenerator;
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
        $this->app->singleton(
            'Flarum\Http\UrlGeneratorInterface',
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
            '/',
            'flarum.forum.index',
            $this->action('Flarum\Forum\Actions\IndexAction')
        );

        $routes->get(
            '/d/{id:\d+}/{slug}',
            'flarum.forum.discussion',
            $this->action('Flarum\Forum\Actions\DiscussionAction')
        );

        $routes->get(
            '/d/{id:\d+}/{slug}/{near}',
            'flarum.forum.discussion.near',
            $this->action('Flarum\Forum\Actions\DiscussionAction')
        );

        $routes->get(
            '/u/{username}',
            'flarum.forum.user',
            $this->action('Flarum\Forum\Actions\IndexAction')
        );

        $routes->get(
            '/settings',
            'flarum.forum.settings',
            $this->action('Flarum\Forum\Actions\IndexAction')
        );

        $routes->get(
            '/logout',
            'flarum.forum.logout',
            $this->action('Flarum\Forum\Actions\LogoutAction')
        );

        $routes->post(
            '/login',
            'flarum.forum.login',
            $this->action('Flarum\Forum\Actions\LoginAction')
        );

        $routes->get(
            '/confirm/{token}',
            'flarum.forum.confirmEmail',
            $this->action('Flarum\Forum\Actions\ConfirmEmailAction')
        );

        $routes->get(
            '/reset/{token}',
            'flarum.forum.resetPassword',
            $this->action('Flarum\Forum\Actions\ResetPasswordAction')
        );

        $routes->post(
            '/reset',
            'flarum.forum.savePassword',
            $this->action('Flarum\Forum\Actions\SavePasswordAction')
        );
    }

    protected function action($class)
    {
        return function (ServerRequestInterface $httpRequest, $routeParams) use ($class) {
            $action = $this->app->make($class);

            return $action->handle($httpRequest, $routeParams);
        };
    }
}
