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

/**
 * This class is used to wrap old bootstrap.php closures (as used in versions up
 * to 0.1.0-beta7) in the new Extender format.
 *
 * This gives extensions the chance to work with the new API without making any
 * changes, and have some time to convert to the pure usage of extenders.
 *
 * @deprecated
 */
class Compat implements ExtenderInterface
{
    protected $callback;

    public function __construct($callback)
    {
        $this->callback = $callback;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $container->call($this->callback);
    }
}
