<?php

declare(strict_types=1);

namespace Flarum\Http\Middleware;

use Flarum\Foundation\Paths;
use Illuminate\Contracts\Container\Container;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface;

class ClearOPCache implements Middleware
{
    const PATH = '/cache/clear-opcache';

    public function __construct(private readonly Container $container)
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
        if ($this->container->bound(Paths::class)) {
            return $this->container->make(Paths::class)->storage . static::PATH;
        }

        // Fallback when Paths is not available during installation.
        $path = dirname(__DIR__, 3);

        while(true) {
            if ($path === '.') throw new \Exception('Could not find storage directory');

            if (is_dir("$path/storage")) break;
            $path = dirname($path);
        }

        return "$path/storage" . static::PATH;
    }
}
