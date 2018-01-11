<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Flarum\Http\RouteHandlerFactory;
use Illuminate\Contracts\Container\Container;

class Routes implements Extender
{
    protected $appName;

    protected $routes = [];

    public function __construct($appName)
    {
        $this->appName = $appName;
    }

    public function get($path, $name, $handler)
    {
        return $this->route('GET', $path, $name, $handler);
    }

    public function post($path, $name, $handler)
    {
        return $this->route('POST', $path, $name, $handler);
    }

    public function put($path, $name, $handler)
    {
        return $this->route('PUT', $path, $name, $handler);
    }

    public function patch($path, $name, $handler)
    {
        return $this->route('PATCH', $path, $name, $handler);
    }

    public function delete($path, $name, $handler)
    {
        return $this->route('DELETE', $path, $name, $handler);
    }

    private function route($httpMethod, $path, $name, $handler)
    {
        $this->routes[] = [
            'method' => $httpMethod,
            'path' => $path,
            'name' => $name,
            'handler' => $handler
        ];

        return $this;
    }

    public function __invoke(Container $container)
    {
        if (empty($this->routes)) {
            return;
        }

        /** @var \Flarum\Http\RouteCollection $collection */
        $collection = $container->make("flarum.{$this->appName}.routes");

        /** @var RouteHandlerFactory $factory */
        $factory = $container->make(RouteHandlerFactory::class);

        foreach ($this->routes as $route) {
            $collection->addRoute(
                $route['method'],
                $route['path'],
                $route['name'],
                $factory->toController($route['handler'])
            );
        }
    }
}
