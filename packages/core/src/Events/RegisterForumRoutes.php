<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Events;

use Flarum\Http\RouteCollection;
use Psr\Http\Message\ServerRequestInterface;

class RegisterForumRoutes
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

    public function get($url, $name, $action = 'Flarum\Forum\Actions\ClientAction')
    {
        $this->route('get', $url, $name, $action);
    }

    protected function route($method, $url, $name, $action)
    {
        $this->routes->$method($url, $name, $this->action($action));
    }

    protected function action($class)
    {
        return function (ServerRequestInterface $httpRequest, $routeParams) use ($class) {
            /** @var \Flarum\Support\Action $action */
            $action = app($class);

            return $action->handle($httpRequest, $routeParams);
        };
    }
}
