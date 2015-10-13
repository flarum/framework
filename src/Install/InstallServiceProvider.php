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

use Flarum\Http\GenerateRouteHandlerTrait;
use Flarum\Http\RouteCollection;
use Flarum\Install\Prerequisite\PhpExtensions;
use Flarum\Install\Prerequisite\PhpVersion;
use Flarum\Install\Prerequisite\WritablePaths;
use Flarum\Install\Prerequisite\Composite;
use Flarum\Foundation\AbstractServiceProvider;
use Psr\Http\Message\ServerRequestInterface;

class InstallServiceProvider extends AbstractServiceProvider
{
    use GenerateRouteHandlerTrait;

    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->app->bind(
            'Flarum\Install\Prerequisite\PrerequisiteInterface',
            function () {
                return new Composite(
                    new PhpVersion(),
                    new PhpExtensions(),
                    new WritablePaths()
                );
            }
        );

        $this->app->singleton('flarum.install.routes', function () {
            return $this->getRoutes();
        });

        $this->loadViewsFrom(__DIR__.'/../../views/install', 'flarum.install');
    }

    /**
     * @return RouteCollection
     */
    protected function getRoutes()
    {
        $routes = new RouteCollection;

        $toController = $this->getHandlerGenerator($this->app);

        $routes->get(
            '/',
            'index',
            $toController('Flarum\Install\Controller\IndexController')
        );

        $routes->post(
            '/',
            'install',
            $toController('Flarum\Install\Controller\InstallController')
        );

        return $routes;
    }
}
