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

    public function set(string $name, array $paths, array $methods, callable $callback)
    {
        $this->setFloodgates[$name] = [
            'paths' => $paths,
            'methods' => $methods,
            'callback' => $callback,
        ];

        return $this;
    }


    public function remove(string $name)
    {
        $this->removeFloodgates[] = $name;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $container->extend("flarum.api.floodgates", function ($floodgates) {
            foreach ($this->removeFloodgates as $floodgateName) {
                if (array_key_exists($floodgateName, $floodgates)) {
                    unset($floodgates[$floodgateName]);
                }
            }

            return array_merge($floodgates, $this->setFloodgates);
        });
    }
}
