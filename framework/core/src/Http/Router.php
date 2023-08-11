<?php

namespace Flarum\Http;

use Illuminate\Routing\Router as IlluminateRouter;

class Router extends IlluminateRouter
{
    public function __construct(...$args)
    {
        parent::__construct(...$args);

        $this->routes = new RouteCollection();
    }

    public function forgetRoute(string $name): void
    {
        $this->routes->forgetNamedRoute($name);
    }
}
