<?php

namespace Flarum\Http;

class UrlGenerator implements UrlGeneratorInterface
{
    protected $routes;


    public function __construct(RouteCollection $routes)
    {
        $this->routes = $routes;
    }

    public function toRoute($name, $parameters = [])
    {
        $path = $this->routes->getPath($name, $parameters);
        $path = ltrim($path, '/');

        // TODO: Prepend real base URL
        return "/$path";
    }

    public function toAsset($path)
    {
        return "/$path";
    }
}
