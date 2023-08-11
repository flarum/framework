<?php

namespace Flarum\Http;

use Illuminate\Contracts\Container\Container;
use Illuminate\Routing\RoutingServiceProvider as IlluminateRoutingServiceProvider;

class RoutingServiceProvider extends IlluminateRoutingServiceProvider
{
    protected function registerRouter(): void
    {
        $this->app->singleton('router', function (Container $container) {
            return new Router($container['events'], $container);
        });
    }
}
