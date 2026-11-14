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
use Psr\Log\LoggerInterface;
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
        $container->extend("flarum.$this->frontend.middleware", function ($existingMiddleware) use ($container) {
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
                $offset = $this->offsetOf($originalMiddleware, $newMiddleware, $existingMiddleware, $container);

                if ($offset !== null) {
                    array_splice($existingMiddleware, $offset, 0, $newMiddleware);
                }
            }

            foreach ($this->insertAfterMiddlewares as $originalMiddleware => $newMiddleware) {
                $offset = $this->offsetOf($originalMiddleware, $newMiddleware, $existingMiddleware, $container);

                if ($offset !== null) {
                    array_splice($existingMiddleware, $offset + 1, 0, $newMiddleware);
                }
            }

            return array_diff($existingMiddleware, $this->removeMiddlewares);
        });
    }

    /**
     * Locate the anchor an insertion is relative to, or null if it is gone.
     *
     * Extenders run in extension order, so another extension may already have
     * replaced or removed the anchor, or it may belong to an extension that is
     * not enabled. The insertion is then skipped with a warning: defaulting to an
     * offset would put the middleware ahead of the error handler, and throwing
     * would take down the whole frontend over a load order the admin cannot fix.
     *
     * @param array<string> $existingMiddleware
     */
    private function offsetOf(string $originalMiddleware, string $newMiddleware, array $existingMiddleware, Container $container): ?int
    {
        $offset = array_search($originalMiddleware, $existingMiddleware, true);

        if ($offset === false) {
            $container->make(LoggerInterface::class)->warning(
                "Skipped inserting middleware [$newMiddleware] relative to [$originalMiddleware]:"
                ." it is not present in the [$this->frontend] middleware stack."
                .' Another extension may have replaced or removed it, or it was never registered.'
            );

            return null;
        }

        return $offset;
    }
}
