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

class Floodgate implements ExtenderInterface
{
    private $setFloodgates = [];
    private $removeFloodgates = [];

    /**
     * Register a flood gate checker.
     *
     * @param string $name: The name of the flood gate checker. These must be unique:
     *                      if you use the name of an existing checker it will be overriden.
     * @param string[] $paths: An array of paths (e.g. /api/discussions) on which to apply this checker.
     *                         Wildcards are supported.
     * @param string[] $methods: An array of methods on which to apply this checker.
     *                           Methods should be uppercase, wildcards are NOT supported.
     * @param string[] $callback: A closure or invokable class that contains the logic of the flood gate.
     *                            It should return one of:
     *                              - `false`: This marks the request as NOT flooding. It overrides all other outputs
     *                              - `true`: This marks the request as flooding.
     *                            All other outputs will be ignored.
     * @return self
     */
    public function set(string $name, array $paths, array $methods, callable $callback)
    {
        $this->setFloodgates[$name] = [
            'paths' => $paths,
            'methods' => $methods,
            'callback' => $callback,
        ];

        return $this;
    }


    /**
     * Remove a flood gate checker registered with this name.
     *
     * @param string $name: The name of the flood gate checker to remove.
     *
     * @return self
     */
    public function remove(string $name)
    {
        $this->removeFloodgates[] = $name;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $container->extend('flarum.api.floodgates', function ($floodgates) {
            foreach ($this->removeFloodgates as $floodgateName) {
                if (array_key_exists($floodgateName, $floodgates)) {
                    unset($floodgates[$floodgateName]);
                }
            }

            return array_merge($floodgates, $this->setFloodgates);
        });
    }
}
