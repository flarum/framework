<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http;

use Flarum\Foundation\Application;

class UrlGenerator
{
    protected array $routes = [];

    public function __construct(
        protected Application $app
    ) {
    }

    /**
     * Register a named route collection for URL generation.
     */
    public function addCollection(string $key, RouteCollection $routes, ?string $prefix = null): static
    {
        $this->routes[$key] = new RouteCollectionUrlGenerator(
            $this->app->url($prefix),
            $routes
        );

        return $this;
    }

    /**
     * Retrieve a URL generator instance for the given named route collection.
     */
    public function to(string $collection): RouteCollectionUrlGenerator
    {
        return $this->routes[$collection];
    }
}
