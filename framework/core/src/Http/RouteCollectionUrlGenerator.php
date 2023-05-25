<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http;

/**
 * @internal
 */
class RouteCollectionUrlGenerator
{
    public function __construct(
        protected string $baseUrl,
        protected RouteCollection $routes
    ) {
    }

    public function route(string $name, array $parameters = []): string
    {
        $path = $this->routes->getPath($name, $parameters);
        $path = ltrim($path, '/');

        return $this->baseUrl.'/'.$path;
    }

    public function path(string $path): string
    {
        return $this->baseUrl.'/'.$path;
    }

    public function base(): string
    {
        return $this->baseUrl;
    }
}
