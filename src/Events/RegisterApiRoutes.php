<?php namespace Flarum\Events;

use Flarum\Http\RouteCollection;

class RegisterApiRoutes
{
    /**
     * @var RouteCollection
     */
    public $routes;

    /**
     * @param RouteCollection $routes
     */
    public function __construct(RouteCollection $routes)
    {
        $this->routes = $routes;
    }
}
