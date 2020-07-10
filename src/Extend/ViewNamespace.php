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

class ViewNamespace implements ExtenderInterface
{
    private $adds = [];

    /**
     * Register a new namespace of laravel views.
     *
     * @param  string  $namespace
     * @param  string|array  $hints
     * @return $this
     */
    public function add($namespace, $hints)
    {
        $this->adds[$namespace] = $hints;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $factory = $container->make(Factory::class);

        foreach ($this->adds as $namespace => $hints) {
            $factory->addNamespace($namespace, $hints);
        }
    }
}
