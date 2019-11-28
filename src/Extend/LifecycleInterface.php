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

interface LifecycleInterface
{
    public function onEnable(Container $container, Extension $extension);

    public function onDisable(Container $container, Extension $extension);
}
