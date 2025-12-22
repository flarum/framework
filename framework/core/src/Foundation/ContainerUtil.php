<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation;

use Illuminate\Contracts\Container\Container;

class ContainerUtil
{
    /**
     * Wraps a callback so that string-based invokable classes get resolved only when actually used.
     *
     * @param callable|class-string $callback : A callable, global function, or a ::class attribute of an invokable class
     *
     * @internal Backwards compatibility not guaranteed.
     */
    public static function wrapCallback(callable|string $callback, Container $container): callable
    {
        if (is_string($callback) && ! is_callable($callback)) {
            $callback = function (&...$args) use ($container, $callback) {
                $callback = $container->make($callback);

                return $callback(...$args);
            };
        }

        return $callback;
    }
}
