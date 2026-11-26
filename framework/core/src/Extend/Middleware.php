<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Flarum\Extension\Extension;
use Illuminate\Contracts\Container\Container;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Log\LoggerInterface;

class Middleware implements ExtenderInterface
{
    private array $addMiddlewares = [];
    private array $removeMiddlewares = [];
    private array $replaceMiddlewares = [];
    private array $insertBeforeMiddlewares = [];
    private array $insertAfterMiddlewares = [];

    /**
     * @param string $frontend: The name of the frontend.
     */
    public function __construct(
        private readonly string $frontend
    ) {
    }

    /**
     * Adds a new middleware to the frontend.
     *
     * @param class-string<MiddlewareInterface> $middleware: ::class attribute of the middleware class.
     *                            Must implement \Psr\Http\Server\MiddlewareInterface.
     */
    public function add(string $middleware): self
    {
        $this->addMiddlewares[] = $middleware;

        return $this;
    }

    /**
     * Replaces an existing middleware of the frontend.
     *
     * @param class-string<MiddlewareInterface> $originalMiddleware: ::class attribute of the original middleware class.
     *                                    Or container binding name.
     * @param class-string<MiddlewareInterface> $newMiddleware: ::class attribute of the middleware class.
     *                            Must implement \Psr\Http\Server\MiddlewareInterface.
     */
    public function replace(string $originalMiddleware, string $newMiddleware): self
    {
        $this->replaceMiddlewares[$originalMiddleware] = $newMiddleware;

        return $this;
    }

    /**
     * Removes a middleware from the frontend.
     *
     * @param class-string<MiddlewareInterface> $middleware: ::class attribute of the middleware class.
     */
    public function remove(string $middleware): self
    {
        $this->removeMiddlewares[] = $middleware;

        return $this;
    }

    /**
     * Inserts a middleware before an existing middleware.
     *
     * Several middlewares inserted before the same one keep the order they were
     * registered in. If the existing middleware is not in the stack, nothing is
     * inserted and a warning is logged, as with {@see replace()}.
     *
     * @param class-string<MiddlewareInterface> $originalMiddleware: ::class attribute of the original middleware class.
     *                                    Or container binding name.
     * @param class-string<MiddlewareInterface> $newMiddleware: ::class attribute of the middleware class.
     *                            Must implement \Psr\Http\Server\MiddlewareInterface.
     */
    public function insertBefore(string $originalMiddleware, string $newMiddleware): self
    {
        $this->insertBeforeMiddlewares[$originalMiddleware][] = $newMiddleware;

        return $this;
    }

    /**
     * Inserts a middleware after an existing middleware.
     *
     * Several middlewares inserted after the same one keep the order they were
     * registered in. If the existing middleware is not in the stack, nothing is
     * inserted and a warning is logged, as with {@see replace()}.
     *
     * @param class-string<MiddlewareInterface> $originalMiddleware: ::class attribute of the original middleware class.
     *                                    Or container binding name.
     * @param class-string<MiddlewareInterface> $newMiddleware: ::class attribute of the middleware class.
     *                            Must implement \Psr\Http\Server\MiddlewareInterface.
     */
    public function insertAfter(string $originalMiddleware, string $newMiddleware): self
    {
        $this->insertAfterMiddlewares[$originalMiddleware][] = $newMiddleware;

        return $this;
    }

    public function extend(Container $container, ?Extension $extension = null): void
    {
        $container->extend("flarum.$this->frontend.middleware", function ($existingMiddleware, Container $container) {
            foreach ($this->addMiddlewares as $addMiddleware) {
                $existingMiddleware[] = $addMiddleware;
            }

            foreach ($this->replaceMiddlewares as $originalMiddleware => $newMiddleware) {
                $existingMiddleware = array_replace(
                    $existingMiddleware,
                    array_fill_keys(
                        array_keys($existingMiddleware, $originalMiddleware),
                        $newMiddleware
                    )
                );
            }

            foreach ($this->insertBeforeMiddlewares as $originalMiddleware => $newMiddlewares) {
                $existingMiddleware = $this->insert($container, $existingMiddleware, $originalMiddleware, $newMiddlewares, 0);
            }

            foreach ($this->insertAfterMiddlewares as $originalMiddleware => $newMiddlewares) {
                $existingMiddleware = $this->insert($container, $existingMiddleware, $originalMiddleware, $newMiddlewares, 1);
            }

            return array_diff($existingMiddleware, $this->removeMiddlewares);
        });
    }

    /**
     * `array_search()` returns false for a missing anchor, which `array_splice()`
     * reads as offset 0: the middleware would silently run first, before the
     * actor or session exist.
     *
     * @param string[] $middlewares
     * @param string[] $newMiddlewares
     * @return string[]
     */
    private function insert(Container $container, array $middlewares, string $anchor, array $newMiddlewares, int $offset): array
    {
        $position = array_search($anchor, $middlewares, true);

        if ($position === false) {
            if ($container->bound(LoggerInterface::class)) {
                $container->make(LoggerInterface::class)->warning(
                    "Middleware [$anchor] is not in the $this->frontend middleware stack; ".implode(', ', $newMiddlewares).' not inserted.'
                );
            }

            return $middlewares;
        }

        array_splice($middlewares, $position + $offset, 0, $newMiddlewares);

        return $middlewares;
    }
}
