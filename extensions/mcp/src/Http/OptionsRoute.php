<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Http;

use Flarum\Extend\ExtenderInterface;
use Flarum\Extension\Extension;
use Flarum\Http\RouteCollection;
use Flarum\Http\RouteHandlerFactory;
use Illuminate\Contracts\Container\Container;

/**
 * Adds the `OPTIONS /mcp` route, which `Extend\Routes` has no method for.
 *
 * @internal
 */
class OptionsRoute implements ExtenderInterface
{
    public function extend(Container $container, ?Extension $extension = null): void
    {
        $container->resolving('flarum.forum.routes', function (RouteCollection $routes, Container $container) {
            $routes->addRoute(
                'OPTIONS',
                '/mcp',
                'mcp.options',
                $container->make(RouteHandlerFactory::class)->toController(McpController::class)
            );
        });
    }
}
