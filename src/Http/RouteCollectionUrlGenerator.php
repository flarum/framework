<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http;

class RouteCollectionUrlGenerator
{
    /**
     * @var string|null
     */
    protected $baseUrl;

    /**
     * @var RouteCollection
     */
    protected $routes;

    /**
     * @param string $baseUrl
     * @param RouteCollection $routes
     */
    public function __construct($baseUrl, RouteCollection $routes)
    {
        $this->baseUrl = $baseUrl;
        $this->routes = $routes;
    }

    /**
     * Generate a URL to a named route.
     *
     * @param string $name
     * @param array $parameters
     * @return string
     */
    public function route($name, $parameters = [])
    {
        $path = $this->routes->getPath($name, $parameters);
        $path = ltrim($path, '/');

        return $this->baseUrl.'/'.$path;
    }

    /**
     * Generate a URL to a path.
     *
     * @param string $path
     * @return string
     */
    public function path($path)
    {
        return $this->baseUrl.'/'.$path;
    }

    /**
     * Generate a URL to base with UrlGenerator's prefix.
     *
     * @return string
     */
    public function base()
    {
        return $this->baseUrl;
    }
}
