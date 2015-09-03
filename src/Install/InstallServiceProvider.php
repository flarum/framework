<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Install;

use Flarum\Http\RouteCollection;
use Flarum\Install\Prerequisites\PhpExtensions;
use Flarum\Install\Prerequisites\PhpVersion;
use Flarum\Install\Prerequisites\WritablePaths;
use Flarum\Install\Prerequisites\Composite;
use Flarum\Support\ServiceProvider;
use Psr\Http\Message\ServerRequestInterface;

class InstallServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register('Flarum\Locale\LocaleServiceProvider');

        $this->app->bind(
            'Flarum\Install\Prerequisites\Prerequisite',
            function () {
                return new Composite(
                    new PhpVersion(),
                    new PhpExtensions(),
                    new WritablePaths()
                );
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

        $this->loadViewsFrom($root.'/views/install', 'flarum.install');

        $this->routes();
    }

    protected function routes()
    {
        $this->app->instance('flarum.install.routes', $routes = new RouteCollection);

        $routes->get(
            '/',
            'flarum.install.index',
            $this->action('Flarum\Install\Actions\IndexAction')
        );

        $routes->post(
            '/',
            'flarum.install.install',
            $this->action('Flarum\Install\Actions\InstallAction')
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
