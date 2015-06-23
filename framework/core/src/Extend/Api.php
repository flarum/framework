<?php namespace Flarum\Extend;

use Illuminate\Contracts\Container\Container;

class Api implements ExtenderInterface
{
    protected $routes = [];

    public function route($method, $url, $name, $action)
    {
        $this->routes[] = compact('method', 'url', 'name', 'action');

        return $this;
    }

    public function extend(Container $container)
    {
        if ($container->make('type') !== 'api') {
            return;
        }

        if (count($this->routes)) {
            $routes = $container->make('flarum.api.routes');

            foreach ($this->routes as $route) {
                $method = $route['method'];
                $routes->$method($route['url'], $route['name'], function (ServerRequestInterface $httpRequest, $routeParams) use ($container, $route) {
                    $action = $container->make($route['action']);

                    return $action->handle($httpRequest, $routeParams);
                });
            }
        }
    }
}
