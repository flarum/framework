<?php namespace Flarum\Admin;

use Flarum\Http\RouteCollection;
use Flarum\Http\UrlGenerator;
use Flarum\Support\AssetManager;
use Illuminate\Support\ServiceProvider;
use Psr\Http\Message\ServerRequestInterface;

class AdminServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('flarum.admin.assetManager', function () {
            return new AssetManager($this->app->make('files'), public_path('assets'), 'admin');
        });

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
            $this->action('Flarum\Admin\Actions\IndexAction')
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
