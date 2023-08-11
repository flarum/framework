<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Flarum\Extend\Concerns\ExtendsRoutes;
use Flarum\Extension\Extension;
use Flarum\Http\Controller\AbstractController;
use Illuminate\Contracts\Container\Container;

class Routes implements ExtenderInterface
{
    use ExtendsRoutes;

    /**
     * @param string $frontend: Name of the app (api, forum, admin).
     */
    public function __construct(
        private readonly string $frontend
    ) {
    }

    /**
     * Add a GET route.
     *
     * @param string $path: The path of the route
     * @param string $name: The name of the route, must be unique.
     * @param callable|class-string<AbstractController> $handler: ::class attribute of the controller class, or a closure.
     *
     * If the handler is a controller class, it should extend \Flarum\Http\Controller,
     * or extend one of the Flarum Api controllers within \Flarum\Api\Controller.
     *
     * The handler should accept:
     * - \Psr\Http\Message\ServerRequestInterface $request
     * - \Tobscure\JsonApi\Document $document: If it extends one of the Flarum Api controllers.
     *
     * The handler should return:
     * - \Psr\Http\Message\ResponseInterface $response
     *
     * @return self
     */
    public function get(string $path, string $name, callable|string $handler): self
    {
        return $this->route('GET', $path, $name, $handler);
    }

    /**
     * Add a POST route.
     *
     * @param string $path: The path of the route
     * @param string $name: The name of the route, must be unique.
     * @param callable|class-string<AbstractController> $handler: ::class attribute of the controller class, or a closure.
     *
     * If the handler is a controller class, it should extend \Flarum\Http\Controller,
     * or extend one of the Flarum Api controllers within \Flarum\Api\Controller.
     *
     * The handler should accept:
     * - \Psr\Http\Message\ServerRequestInterface $request
     * - \Tobscure\JsonApi\Document $document: If it extends one of the Flarum Api controllers.
     *
     * The handler should return:
     * - \Psr\Http\Message\ResponseInterface $response
     *
     * @return self
     */
    public function post(string $path, string $name, callable|string $handler): self
    {
        return $this->route('POST', $path, $name, $handler);
    }

    /**
     * Add a PUT route.
     *
     * @param string $path: The path of the route
     * @param string $name: The name of the route, must be unique.
     * @param callable|class-string<AbstractController> $handler: ::class attribute of the controller class, or a closure.
     *
     * If the handler is a controller class, it should extend \Flarum\Http\Controller,
     * or extend one of the Flarum Api controllers within \Flarum\Api\Controller.
     *
     * The handler should accept:
     * - \Psr\Http\Message\ServerRequestInterface $request
     * - \Tobscure\JsonApi\Document $document: If it extends one of the Flarum Api controllers.
     *
     * The handler should return:
     * - \Psr\Http\Message\ResponseInterface $response
     *
     * @return self
     */
    public function put(string $path, string $name, callable|string $handler): self
    {
        return $this->route('PUT', $path, $name, $handler);
    }

    /**
     * Add a PATCH route.
     *
     * @param string $path: The path of the route
     * @param string $name: The name of the route, must be unique.
     * @param callable|class-string<AbstractController> $handler: ::class attribute of the controller class, or a closure.
     *
     * If the handler is a controller class, it should extend \Flarum\Http\Controller,
     * or extend one of the Flarum Api controllers within \Flarum\Api\Controller.
     *
     * The handler should accept:
     * - \Psr\Http\Message\ServerRequestInterface $request
     * - \Tobscure\JsonApi\Document $document: If it extends one of the Flarum Api controllers.
     *
     * The handler should return:
     * - \Psr\Http\Message\ResponseInterface $response
     *
     * @return self
     */
    public function patch(string $path, string $name, callable|string $handler): self
    {
        return $this->route('PATCH', $path, $name, $handler);
    }

    /**
     * Add a DELETE route.
     *
     * @param string $path: The path of the route
     * @param string $name: The name of the route, must be unique.
     * @param callable|class-string<AbstractController> $handler: ::class attribute of the controller class, or a closure.
     *
     * If the handler is a controller class, it should extend \Flarum\Http\Controller,
     * or extend one of the Flarum Api controllers within \Flarum\Api\Controller.
     *
     * The handler should accept:
     * - \Psr\Http\Message\ServerRequestInterface $request
     * - \Tobscure\JsonApi\Document $document: If it extends one of the Flarum Api controllers.
     *
     * The handler should return:
     * - \Psr\Http\Message\ResponseInterface $response
     *
     * @return self
     */
    public function delete(string $path, string $name, callable|string $handler): self
    {
        return $this->route('DELETE', $path, $name, $handler);
    }

    private function route(string $httpMethod, string $path, string $name, callable|string $handler): self
    {
        $this->routes[] = [
            'method' => $httpMethod,
            'path' => $path,
            'name' => $name,
            'handler' => $handler
        ];

        return $this;
    }

    /**
     * Remove an existing route.
     * Necessary before overriding a route.
     *
     * @param string $name: The name of the route.
     * @return self
     */
    public function remove(string $name): self
    {
        $this->removedRoutes[] = $name;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null): void
    {
        $this->registerRoutes($container);
    }
}
