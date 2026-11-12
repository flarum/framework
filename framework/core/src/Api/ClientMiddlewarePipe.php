<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Collection;
use Laminas\Stratigility\MiddlewarePipe;
use Laminas\Stratigility\MiddlewarePipeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ClientMiddlewarePipe implements MiddlewarePipeInterface
{
    protected const ERROR_HANDLER = 'flarum.api.error_handler';

    protected Collection $middlewares;
    /** Null until errorHandling() is called: the configured list is used as it is. */
    protected ?bool $handleErrors = null;

    /**
     * Built pipes, keyed by the error handling mode they were built for.
     *
     * @var array<string, MiddlewarePipeInterface>
     */
    protected array $pipes = [];

    public function __construct(
        protected Container $container,
        array $middlewares
    ) {
        $this->middlewares = new Collection($middlewares);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $this->getPipe()->process($request, $handler);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->getPipe()->handle($request);
    }

    public function pipe(MiddlewareInterface $middleware): void
    {
        $this->middlewares->push($middleware);

        $this->pipes = [];
    }

    protected function getPipe(): MiddlewarePipeInterface
    {
        return $this->pipes[var_export($this->handleErrors, true)] ??= $this->buildPipe($this->handleErrors);
    }

    protected function buildPipe(?bool $handleErrors): MiddlewarePipeInterface
    {
        // The configured list is never reordered, so the error handler keeps its place.
        $middlewares = $handleErrors === null ? $this->middlewares : $this->middlewares->reject(self::ERROR_HANDLER);

        if ($handleErrors) {
            $middlewares = $this->middlewares->contains(self::ERROR_HANDLER)
                ? $this->middlewares
                : $middlewares->prepend(self::ERROR_HANDLER);
        }

        $pipe = new MiddlewarePipe();

        foreach ($middlewares as $middleware) {
            $pipe->pipe(is_string($middleware) ? $this->container->make($middleware) : $middleware);
        }

        return $pipe;
    }

    public function errorHandling(bool $handleErrors): static
    {
        $this->handleErrors = $handleErrors;

        return $this;
    }
}
