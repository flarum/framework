<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http;

use Illuminate\Routing\RouteCollection as IlluminateRouteCollection;

/**
 * @internal
 */
class RouteCollection extends IlluminateRouteCollection
{
    public function forgetNamedRoute($name): void
    {
        $route = $this->getByName($name);

        if (! $route) {
            return;
        }

        // Remove the quick lookup.
        unset($this->nameList[$name]);

        // Remove from the routes.
        foreach ($route->methods() as $method) {
            unset($this->routes[$method][$route->getDomain().$route->uri()]);
            unset($this->allRoutes[$method.$route->getDomain().$route->uri()]);
        }
    }
}
