<?php

namespace Flarum\Api\Resource\Concerns;

use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;

trait Bootable
{
    protected readonly Container $container;
    protected readonly Dispatcher $events;

    public function boot(Container $container): void
    {
        $this->container = $container;
        $this->events = $container->make(Dispatcher::class);
    }
}
