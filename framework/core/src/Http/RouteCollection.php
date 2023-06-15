<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http;

use FastRoute\DataGenerator;
use FastRoute\RouteParser;
use Illuminate\Support\Arr;

/**
 * @internal
 */
class RouteCollection
{
    protected array $reverse = [];
    protected DataGenerator $dataGenerator;
    protected RouteParser $routeParser;
    protected array $routes = [];
    protected array $pendingRoutes = [];

    public function __construct()
    {
        $this->dataGenerator = new DataGenerator\GroupCountBased;
        $this->routeParser = new RouteParser\Std;
    }

    public function get(string $path, string $name, callable|string $handler): self
    {
        return $this->addRoute('GET', $path, $name, $handler);
    }

    public function post(string $path, string $name, callable|string $handler): self
    {
        return $this->addRoute('POST', $path, $name, $handler);
    }

    public function put(string $path, string $name, callable|string $handler): self
    {
        return $this->addRoute('PUT', $path, $name, $handler);
    }

    public function patch(string $path, string $name, callable|string $handler): self
    {
        return $this->addRoute('PATCH', $path, $name, $handler);
    }

    public function delete(string $path, string $name, callable|string $handler): self
    {
        return $this->addRoute('DELETE', $path, $name, $handler);
    }

    public function addRoute(string $method, string $path, string $name, callable|string $handler): self
    {
        if (isset($this->routes[$name])) {
            throw new \RuntimeException("Route $name already exists");
        }

        $this->routes[$name] = $this->pendingRoutes[$name] = compact('method', 'path', 'handler');

        return $this;
    }

    public function removeRoute(string $name): self
    {
        unset($this->routes[$name], $this->pendingRoutes[$name]);

        return $this;
    }

    protected function applyRoutes(): void
    {
        foreach ($this->pendingRoutes as $name => $route) {
            $routeDatas = $this->routeParser->parse($route['path']);

            foreach ($routeDatas as $routeData) {
                $this->dataGenerator->addRoute($route['method'], $routeData, ['name' => $name, 'handler' => $route['handler']]);
            }

            $this->reverse[$name] = $routeDatas;
        }

        $this->pendingRoutes = [];
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function getRouteData(): array
    {
        if (! empty($this->pendingRoutes)) {
            $this->applyRoutes();
        }

        return $this->dataGenerator->getData();
    }

    protected function fixPathPart(mixed $part, array $parameters, string $routeName): string
    {
        if (! is_array($part)) {
            return $part;
        }

        if (! array_key_exists($part[0], $parameters)) {
            throw new \InvalidArgumentException("Could not generate URL for route '$routeName': no value provided for required part '$part[0]'.");
        }

        return $parameters[$part[0]];
    }

    public function getPath(string $name, array $parameters = []): string
    {
        if (! empty($this->pendingRoutes)) {
            $this->applyRoutes();
        }

        if (isset($this->reverse[$name])) {
            $maxMatches = 0;
            $matchingParts = $this->reverse[$name][0];

            // For a given route name, we want to choose the option that best matches the given parameters.
            // Each routing option is an array of parts. Each part is either a constant string
            // (which we don't care about here), or an array where the first element is the parameter name
            // and the second element is a regex into which the parameter value is inserted, if the parameter matches.
            foreach ($this->reverse[$name] as $parts) {
                foreach ($parts as $i => $part) {
                    if (is_array($part) && Arr::exists($parameters, $part[0]) && $i > $maxMatches) {
                        $maxMatches = $i;
                        $matchingParts = $parts;
                    }
                }
            }

            $fixedParts = array_map(function ($part) use ($parameters, $name) {
                return $this->fixPathPart($part, $parameters, $name);
            }, $matchingParts);

            return '/'.ltrim(implode('', $fixedParts), '/');
        }

        throw new \RuntimeException("Route $name not found");
    }
}
