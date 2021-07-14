<?php

namespace Flarum\Queue\Driver;

use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Queue\Queue;

interface DriverInterface
{
    public function build(): Queue;
    public function setContainer(Container $container): self;
}
