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
     * @param string[] $callback: A closure or invokable class that contains the logic of the throttler.
     *                            It should return one of:
     *                              - `false`: This marks the request as NOT flooding. It overrides all other outputs
     *                              - `true`: This marks the request as flooding.
     *                            All other outputs will be ignored.
     * @return self
     */
    public function set(string $name, callable $callback)
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
