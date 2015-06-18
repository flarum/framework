<?php namespace Flarum\Extend;

use Illuminate\Contracts\Container\Container;
use Flarum\Forum\Actions\IndexAction;
use Psr\Http\Message\ServerRequestInterface;

class ForumClient implements ExtenderInterface
{
    protected $assets = [];

    protected $translations = [];

    protected $routes = [];

    public function assets($assets)
    {
        $this->assets = array_merge($this->assets, $assets);

        return $this;
    }

    public function translations($keys)
    {
        $this->translations = array_merge($this->translations, $keys);

        return $this;
    }

    public function route($method, $url, $name, $action = 'Flarum\Forum\Actions\IndexAction')
    {
        $this->routes[] = compact('method', 'url', 'name', 'action');

        return $this;
    }

    public function extend(Container $container)
    {
        if ($container->make('type') !== 'forum') {
            return;
        }

        $container->make('events')->listen('Flarum\Forum\Events\RenderView', function ($event) {
            $event->assets->addFiles($this->assets);
        });

        IndexAction::$translations = array_merge(IndexAction::$translations, $this->translations);

        if (count($this->routes)) {
            $routes = $container->make('flarum.forum.routes');

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
