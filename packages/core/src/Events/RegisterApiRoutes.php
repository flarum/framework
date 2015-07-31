<?php namespace Flarum\Events;

use Flarum\Api\Request;
use Flarum\Http\RouteCollection;
use Psr\Http\Message\ServerRequestInterface;

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

    public function get($url, $name, $action)
    {
        $this->route('get', $url, $name, $action);
    }

    public function patch($url, $name, $action)
    {
        $this->route('patch', $url, $name, $action);
    }

    protected function route($method, $url, $name, $action)
    {
        $this->routes->$method($url, $name, $this->action($action));
    }

    protected function action($class)
    {
        return function (ServerRequestInterface $httpRequest, $routeParams) use ($class) {
            $action = app($class);
            $actor = app('flarum.actor');

            $input = array_merge($httpRequest->getQueryParams(), $httpRequest->getAttributes(), $routeParams);
            $request = new Request($input, $actor, $httpRequest);

            return $action->handle($request);
        };
    }
}
