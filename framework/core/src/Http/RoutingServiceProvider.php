<?php

namespace Flarum\Http;

use Flarum\Foundation\Config;
use Illuminate\Contracts\Container\Container;
use Illuminate\Routing\RoutingServiceProvider as IlluminateRoutingServiceProvider;

class RoutingServiceProvider extends IlluminateRoutingServiceProvider
{
    protected function registerRouter(): void
    {
        $this->app->singleton('router', function (Container $container) {
            return new Router($container['events'], $container);
        });

        $this->app->booted(function (Container $container) {
            $container['router']->getRoutes()->refreshNameLookups();
            $container['router']->getRoutes()->refreshActionLookups();
        });
    }

    protected function registerUrlGenerator(): void
    {
        $this->app->singleton('url', function (Container $container) {
            $routes = $container['router']->getRoutes();

            // The URL generator needs the route collection that exists on the router.
            // Keep in mind this is an object, so we're passing by references here
            // and all the registered routes will be available to the generator.
            $container->instance('routes', $routes);

            $url = new UrlGenerator(
                $routes, $container->rebinding(
                    'request', $this->requestRebinder()
                ), $container['config']['app.asset_url']
            );

            $url->setConfig(
                $container->make(Config::class)
            );

            return $url;
        });
    }
}
