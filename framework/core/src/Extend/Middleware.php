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
use Laminas\Stratigility\MiddlewarePipe;

class Middleware implements ExtenderInterface
{
    protected $middlewares = [];
    protected $frontend;

    public function __construct(string $frontend)
    {
        $this->frontend = $frontend;
    }

    public function add($middleware)
    {
        $this->middlewares[] = $middleware;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $container->resolving("flarum.{$this->frontend}.middleware", function (MiddlewarePipe $pipe) use ($container) {
            foreach ($this->middlewares as $middleware) {
                $pipe->pipe($container->make($middleware));
            }
        });
    }
}
