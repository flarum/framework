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

class Middleware implements ExtenderInterface
{
    private $addMiddlewares = [];
    private $removeMiddlewares = [];
    private $replaceMiddlewares = [];
    private $insertBeforeMiddlewares = [];
    private $insertAfterMiddlewares = [];
    private $frontend;

    public function __construct(string $frontend)
    {
        $this->frontend = $frontend;
    }

    public function add($middleware)
    {
        $this->addMiddlewares[] = $middleware;

        return $this;
    }

    public function replace($originalMiddleware, $newMiddleware)
    {
        $this->replaceMiddlewares[$originalMiddleware] = $newMiddleware;

        return $this;
    }

    public function remove($middleware)
    {
        $this->removeMiddlewares[] = $middleware;

        return $this;
    }

    public function insertBefore($originalMiddleware, $newMiddleware)
    {
        $this->insertBeforeMiddlewares[$originalMiddleware] = $newMiddleware;

        return $this;
    }

    public function insertAfter($originalMiddleware, $newMiddleware)
    {
        $this->insertAfterMiddlewares[$originalMiddleware] = $newMiddleware;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $container->extend("flarum.{$this->frontend}.middleware", function ($existingMiddleware) {
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
                    array_search($originalMiddleware, $existingMiddleware),
                    0,
                    $newMiddleware
                );
            }

            foreach ($this->insertAfterMiddlewares as $originalMiddleware => $newMiddleware) {
                array_splice(
                    $existingMiddleware,
                    array_search($originalMiddleware, $existingMiddleware) + 1,
                    0,
                    $newMiddleware
                );
            }

            $existingMiddleware = array_diff($existingMiddleware, $this->removeMiddlewares);

            return $existingMiddleware;
        });
    }
}
