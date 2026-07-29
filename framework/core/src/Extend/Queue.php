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

class Queue implements ExtenderInterface
{
    /**
     * @var array<class-string, string>
     */
    private array $routes = [];

    /**
     * Route jobs of a class onto a named queue.
     *
     * The queue is applied when the job is pushed (see
     * {@see \Flarum\Queue\RoutingQueue}), so dispatch sites do not need to
     * change and the routing decision is not stored on the job class.
     *
     * Routing a base/abstract class covers all of its subclasses: e.g. routing
     * an abstract `GamilyticsJob` sends every job that extends it to that queue.
     * The most specific registered class in a job's hierarchy wins, so a route
     * on a concrete subclass overrides one on its base. An explicit queue passed
     * at push time always takes precedence over any route.
     *
     * @param class-string $jobClass  A job extending Flarum\Queue\AbstractJob (or a base class of one).
     * @param string       $queue     The queue name to dispatch its instances on.
     */
    public function route(string $jobClass, string $queue): self
    {
        $this->routes[$jobClass] = $queue;

        return $this;
    }

    public function extend(Container $container, ?Extension $extension = null): void
    {
        if (empty($this->routes)) {
            return;
        }

        // Register the routes with Laravel's native queue-route manager, which
        // resolves a job's queue by its class and hierarchy (parents,
        // interfaces, traits). Flarum's queue connection consults it on push.
        $routes = $container->make('queue.routes');

        foreach ($this->routes as $jobClass => $queue) {
            $routes->set($jobClass, $queue);
        }
    }
}
