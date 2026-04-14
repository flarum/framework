<?php

declare(strict_types=1);

namespace Flarum\Http\Middleware;

use Flarum\Foundation\Paths;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface;

class ClearOPCache implements Middleware
{
    const PATH = '/cache/clear-opcache';

    public function __construct(private readonly Paths $paths)
    {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (! file_exists($this->path()) || ! function_exists('opcache_reset')) {
            return $handler->handle($request);
        }

        opcache_reset();

        @unlink($this->path());

        return $handler->handle($request);
    }

    public function path(): string
    {
        return $this->paths->storage . static::PATH;
    }
}
