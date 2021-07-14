<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Queue\Driver;

use Illuminate\Contracts\Container\Container;

abstract class Driver implements DriverInterface
{
    protected ?Container $container = null;

    public function setContainer(Container $container): DriverInterface
    {
        $this->container = $container;

        return $this;
    }
}
