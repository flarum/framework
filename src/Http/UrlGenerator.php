<?php

namespace Flarum\Http;

class UrlGenerator implements UrlGeneratorInterface
{
    protected $router;


    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function toRoute($name, $parameters = [])
    {
        $path = $this->router->getPath($name, $parameters);
        $path = ltrim($path, '/');

        // TODO: Prepend real base URL
        return "/$path";
    }

    public function toAsset($path)
    {
        return "/$path";
    }
}
