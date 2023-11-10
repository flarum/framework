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
    protected Collection $middlewares;
    protected MiddlewarePipeInterface $pipe;

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
    }

    protected function getPipe(): MiddlewarePipeInterface
    {
        if (isset($this->pipe)) {
            return $this->pipe;
        }

        $this->pipe = new MiddlewarePipe();

        foreach ($this->middlewares as $middleware) {
            $this->pipe->pipe($this->container->make($middleware));
        }

        return $this->pipe;
    }

    public function errorHandling(bool $handleErrors): static
    {
        $errorHandler = 'flarum.api.error_handler';

        if ($handleErrors && ! $this->middlewares->contains($errorHandler)) {
            $this->middlewares = $this->middlewares->prepend($errorHandler);
        } elseif (! $handleErrors && $this->middlewares->contains($errorHandler)) {
            $this->middlewares = $this->middlewares->reject($errorHandler);
        }

        return $this;
    }
}
