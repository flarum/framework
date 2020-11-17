<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http;

use Flarum\Database\AbstractModel;
use Flarum\Foundation\Application;

class UrlGenerator
{
    /**
     * @var array
     */
    protected $routes = [];

    /**
     * @var Application
     */
    protected $app;

    /**
     * @var array
     */
    protected $resourceUrlGenerators;

    /**
     * @param Application $app
     */
    public function __construct(Application $app, array $resourceUrlGenerators)
    {
        $this->app = $app;
        $this->resourceUrlGenerators = $resourceUrlGenerators;
    }

    /**
     * Register a named route collection for URL generation.
     *
     * @param string $key
     * @param RouteCollection $routes
     * @param string $prefix
     * @return static
     */
    public function addCollection($key, RouteCollection $routes, $prefix = null)
    {
        $this->routes[$key] = new RouteCollectionUrlGenerator(
            $this->app->url($prefix),
            $routes
        );

        return $this;
    }

    /**
     * Retrieve an URL generator instance for the given named route collection.
     *
     * @param string $collection
     * @return RouteCollectionUrlGenerator
     */
    public function to($collection)
    {
        return $this->routes[$collection];
    }

    /**
     * Generate a URL to an instance of a resource
     *
     * @param string $resourceClass
     * @param AbstractModel $instance
     * @param $args
     * @return void
     */
    public function toResource(string $resourceClass, AbstractModel $instance, ...$args): string
    {
        $callback = $this->resourceUrlGenerators[$resourceClass];

        return $callback($this, $instance, ...$args);
    }
}
