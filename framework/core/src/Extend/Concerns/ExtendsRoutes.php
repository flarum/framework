<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extend\Concerns;

use Flarum\Foundation\Config;
use Flarum\Http\RouteHandlerFactory;
use Flarum\Http\Router;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Foundation\Application;

trait ExtendsRoutes
{
    private array $routes = [];
    private array $removedRoutes = [];

    protected function registerRoutes(Container $container): void
    {
        if (empty($this->routes) && empty($this->removedRoutes)) {
            return;
        }

        $container->make(Application::class)->booted(
            function (Container $container) {
                /** @var RouteHandlerFactory $factory */
                $factory = $container->make(RouteHandlerFactory::class);
                /** @var Router $router */
                $router = $container->make(Router::class);
                /** @var Config $config */
                $config = $container->make(Config::class);

                foreach ($this->removedRoutes as $routeName) {
                    $router->forgetRoute($routeName);
                }

                foreach ($this->routes as $route) {
                    if ($router->has($route['name'])) {
                        throw new \RuntimeException("Route name '{$route['name']}' is already in use.");
                    }

                    $action = isset($route['handler'])
                        ? $factory->toController($route['handler'])
                        : $factory->toFrontend($this->frontend, $route['content']);

                    $router
                        ->addRoute($route['method'], $route['path'], $action)
                        ->prefix($config->path($this->frontend))
                        ->name("$this->frontend.{$route['name']}");
                }
            }
        );
    }
}
