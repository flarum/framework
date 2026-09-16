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
     * @param class-string<MiddlewareInterface> $originalMiddleware: ::class attribute of the original middleware class.
     *                                    Or container binding name.
     * @param class-string<MiddlewareInterface> $newMiddleware: ::class attribute of the middleware class.
     *                            Must implement \Psr\Http\Server\MiddlewareInterface.
     */
    public function insertBefore(string $originalMiddleware, string $newMiddleware): self
    {
        $this->insertBeforeMiddlewares[$originalMiddleware] = $newMiddleware;

        return $this;
    }

    /**
     * Inserts a middleware after an existing middleware.
     *
     * @param class-string<MiddlewareInterface> $originalMiddleware: ::class attribute of the original middleware class.
     *                                    Or container binding name.
     * @param class-string<MiddlewareInterface> $newMiddleware: ::class attribute of the middleware class.
     *                            Must implement \Psr\Http\Server\MiddlewareInterface.
     */
    public function insertAfter(string $originalMiddleware, string $newMiddleware): self
    {
        $this->insertAfterMiddlewares[$originalMiddleware] = $newMiddleware;

        return $this;
    }

    public function extend(Container $container, ?Extension $extension = null): void
    {
        $container->extend("flarum.$this->frontend.middleware", function ($existingMiddleware) {
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

            foreach ($this->insertBeforeMiddlewares as $originalMiddleware => $newMiddleware) {
                array_splice(
                    $existingMiddleware,
                    $this->offsetOf($originalMiddleware, $newMiddleware, $existingMiddleware),
                    0,
                    $newMiddleware
                );
            }

            foreach ($this->insertAfterMiddlewares as $originalMiddleware => $newMiddleware) {
                array_splice(
                    $existingMiddleware,
                    $this->offsetOf($originalMiddleware, $newMiddleware, $existingMiddleware) + 1,
                    0,
                    $newMiddleware
                );
            }

            return array_diff($existingMiddleware, $this->removeMiddlewares);
        });
    }

    /**
     * Locate the anchor an insertion is relative to.
     *
     * Extenders run in extension order, so another extension may already have
     * replaced or removed the anchor. Throwing beats defaulting to an offset: the
     * front of the stack sits ahead of the error handler.
     *
     * @param array<string> $existingMiddleware
     */
    private function offsetOf(string $originalMiddleware, string $newMiddleware, array $existingMiddleware): int
    {
        $offset = array_search($originalMiddleware, $existingMiddleware, true);

        if ($offset === false) {
            throw new \InvalidArgumentException(
                "Cannot insert middleware [$newMiddleware] relative to [$originalMiddleware]:"
                ." it is not present in the [$this->frontend] middleware stack."
                .' Another extension may have replaced or removed it, or it was never registered.'
            );
        }

        return $offset;
    }
}
