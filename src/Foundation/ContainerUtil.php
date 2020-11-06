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
     * @internal Backwards compatability not guarunteed.
     *
     * @param callable|string $callback: A callable, or a ::class attribute of an invokable class
     * @param Container $container
     */
    public static function wrapCallback($callback, Container $container)
    {
        if (is_string($callback)) {
            $callback = function () use ($container, $callback) {
                $callback = $container->make($callback);

                return call_user_func_array($callback, func_get_args());
            };
        }

        return $callback;
    }
}
