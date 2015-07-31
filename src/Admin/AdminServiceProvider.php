<?php namespace Flarum\Admin;

use Flarum\Http\RouteCollection;
use Flarum\Http\UrlGenerator;
use Illuminate\Support\ServiceProvider;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\RedirectResponse;

class AdminServiceProvider extends ServiceProvider
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
                return new UrlGenerator($this->app->make('flarum.admin.routes'));
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

        $this->loadViewsFrom($root.'/views', 'flarum.admin');

        $this->publishes([
            $root.'/public/fonts' => public_path('assets/fonts')
        ]);

        $this->routes();
    }

    protected function routes()
    {
        $this->app->instance('flarum.admin.routes', $routes = new RouteCollection);

        $routes->get(
            '/',
            'flarum.admin.index',
            $this->action('Flarum\Admin\Actions\ClientAction')
        );

        $routes->post(
            '/config',
            'flarum.admin.updateConfig',
            $this->action('Flarum\Admin\Actions\UpdateConfigAction')
        );

        $routes->post(
            '/permission',
            'flarum.admin.updatePermission',
            $this->action('Flarum\Admin\Actions\UpdatePermissionAction')
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
