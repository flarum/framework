<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation;

class ContainerUtil
{
    public static function wrapCallback($callback, $container)
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
