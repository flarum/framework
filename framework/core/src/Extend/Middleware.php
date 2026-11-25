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
use RuntimeException;

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
     * If another extension replaced the anchor, the middleware is inserted relative
     * to its replacement. If the anchor is not in the stack at all, booting the
     * frontend fails, unless `$optional` is set: the insertion is then skipped and
     * a warning logged. Use it for an anchor owned by an extension that may not
     * be enabled.
     *
     * @param class-string<MiddlewareInterface> $originalMiddleware: ::class attribute of the original middleware class.
     *                                    Or container binding name.
     * @param class-string<MiddlewareInterface> $newMiddleware: ::class attribute of the middleware class.
     *                            Must implement \Psr\Http\Server\MiddlewareInterface.
     */
    public function insertBefore(string $originalMiddleware, string $newMiddleware, bool $optional = false): self
    {
        $this->insertBeforeMiddlewares[$originalMiddleware] = [$newMiddleware, $optional];

        return $this;
    }

    /**
     * Inserts a middleware after an existing middleware.
     *
     * Missing and replaced anchors are handled as in {@see insertBefore()}.
     *
     * @param class-string<MiddlewareInterface> $originalMiddleware: ::class attribute of the original middleware class.
     *                                    Or container binding name.
     * @param class-string<MiddlewareInterface> $newMiddleware: ::class attribute of the middleware class.
     *                            Must implement \Psr\Http\Server\MiddlewareInterface.
     */
    public function insertAfter(string $originalMiddleware, string $newMiddleware, bool $optional = false): self
    {
        $this->insertAfterMiddlewares[$originalMiddleware] = [$newMiddleware, $optional];

        return $this;
    }

    public function extend(Container $container, ?Extension $extension = null): void
    {
        $container->extend("flarum.$this->frontend.middleware", function ($existingMiddleware) use ($container) {
            // An earlier extender's removal leaves gaps in the keys, and the
            // insertions below need a key to be a position.
            $existingMiddleware = array_values($existingMiddleware);

            foreach ($this->addMiddlewares as $addMiddleware) {
                $existingMiddleware[] = $addMiddleware;
            }

            $replacements = $this->replacements($container);

            foreach ($this->replaceMiddlewares as $originalMiddleware => $newMiddleware) {
                $positions = array_keys($existingMiddleware, $originalMiddleware, true);

                // Only a replacement that happened is recorded: one of an absent
                // middleware must neither overwrite an earlier mapping nor make
                // the absent one a valid anchor.
                if ($positions) {
                    $existingMiddleware = array_replace($existingMiddleware, array_fill_keys($positions, $newMiddleware));
                    $replacements[$originalMiddleware] = $newMiddleware;
                }
            }

            $container->instance("flarum.$this->frontend.middleware.replacements", $replacements);

            foreach ($this->insertBeforeMiddlewares as $originalMiddleware => [$newMiddleware, $optional]) {
                $offset = $this->offsetOf($originalMiddleware, $newMiddleware, $optional, $existingMiddleware, $replacements, $container);

                if ($offset !== null) {
                    array_splice($existingMiddleware, $offset, 0, $newMiddleware);
                }
            }

            foreach ($this->insertAfterMiddlewares as $originalMiddleware => [$newMiddleware, $optional]) {
                $offset = $this->offsetOf($originalMiddleware, $newMiddleware, $optional, $existingMiddleware, $replacements, $container);

                if ($offset !== null) {
                    array_splice($existingMiddleware, $offset + 1, 0, $newMiddleware);
                }
            }

            return array_diff($existingMiddleware, $this->removeMiddlewares);
        });
    }

    /**
     * Every replacement made to this frontend's stack by extenders applied so far.
     *
     * Kept in the container because extenders run in extension order, each seeing
     * only the stack the previous ones left: an anchor named by a later extender
     * may already have been swapped out.
     *
     * @return array<string, string>
     */
    private function replacements(Container $container): array
    {
        $key = "flarum.$this->frontend.middleware.replacements";

        return $container->bound($key) ? $container->make($key) : [];
    }

    /**
     * @param array<string> $existingMiddleware
     * @param array<string, string> $replacements
     */
    private function offsetOf(string $originalMiddleware, string $newMiddleware, bool $optional, array $existingMiddleware, array $replacements, Container $container): ?int
    {
        $anchor = $originalMiddleware;
        $seen = [];

        while (! in_array($anchor, $existingMiddleware, true) && isset($replacements[$anchor]) && ! isset($seen[$anchor])) {
            $seen[$anchor] = true;
            $anchor = $replacements[$anchor];
        }

        $offset = array_search($anchor, $existingMiddleware, true);

        if ($offset !== false) {
            return $offset;
        }

        $message = "Cannot insert middleware [$newMiddleware] relative to [$originalMiddleware]:"
            ." it is not present in the [$this->frontend] middleware stack."
            .' Another extension may have removed it, or it was never registered.';

        if (! $optional) {
            throw new RuntimeException($message.' Pass $optional to skip the insertion instead.');
        }

        $container->make(LoggerInterface::class)->warning($message.' The insertion was skipped.');

        return null;
    }
}
