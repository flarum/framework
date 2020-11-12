<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Flarum\Extension\Extension;
use Flarum\Foundation\ContainerUtil;
use Illuminate\Contracts\Container\Container;

class ThrottleApi implements ExtenderInterface
{
    private $setThrottlers = [];
    private $removeThrottlers = [];

    /**
     * Add a new throttler (or override one with the same name).
     *
     * @param string $name: The name of the throttler.
     * @param string|callable $callback
     *
     * The callable can be a closure or invokable class, and should accept:
     *   - $request: The current `\Psr\Http\Message\ServerRequestInterface` request object.
     *               `$request->getAttribute('actor')` can be used to get the current user.
     *               `$request->getAttribute('routeName')` can be used to get the current route.
     * Please note that every throttler runs by default on every route.
     * If you only want to throttle certain routes, you'll need to check for that inside your logic.
     *
     * The callable should return one of:
     *   - `false`: This marks the request as NOT to be throttled. It overrides all other throttlers
     *   - `true`: This marks the request as to be throttled.
     *  All other outputs will be ignored.
     *
     * @return self
     */
    public function set(string $name, $callback)
    {
        $this->setThrottlers[$name] = $callback;

        return $this;
    }

    /**
     * Remove a throttler registered with this name.
     *
     * @param string $name: The name of the throttler to remove.
     *
     * @return self
     */
    public function remove(string $name)
    {
        $this->removeThrottlers[] = $name;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $container->extend('flarum.api.throttlers', function ($throttlers) use ($container) {
            $throttlers = array_diff_key($throttlers, array_flip($this->removeThrottlers));

            foreach ($this->setThrottlers as $name => $throttler) {
                $throttlers[$name] = ContainerUtil::wrapCallback($throttler, $container);
            }

            return $throttlers;
        });
    }
}
