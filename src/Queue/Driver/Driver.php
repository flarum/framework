<?php

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
