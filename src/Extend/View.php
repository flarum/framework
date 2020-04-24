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
use Illuminate\Contracts\View\Factory;

class View implements ExtenderInterface
{

    private $addNamespaces = [];

    /**
     * Register a new namespace of laravel views.
     *
     * @param  string  $namespace: The name of the namespace
     * @param  string|array  $hints: a path hint, or an array of path hints.
     * @return $this
     */
    public function addNamespace($namespace, $hints)
    {
        $this->addNamespaces[$namespace] = $hints;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $factory = $container->make(Factory::class);

        foreach ($this->addNamespaces as $namespace => $hints) {
            $factory->addNamespace($namespace, $hints);
        }
    }
}
