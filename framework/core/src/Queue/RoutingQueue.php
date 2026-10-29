<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Queue;

use Illuminate\Contracts\Queue\Queue;
use Illuminate\Queue\QueueRoutes;

/**
 * Wraps the queue connection so jobs are routed onto their registered queue
 * when they are pushed.
 *
 * Laravel's native queue routing ({@see QueueRoutes}) is only applied by the
 * bus dispatcher (`dispatch()`); the low-level `push()` — which most of Flarum
 * and its extensions use — bypasses it. This wrapper closes that gap: when a
 * job is pushed with no explicit queue, its class is looked up in the route map
 * (which resolves through the class hierarchy: parents, interfaces, traits) and
 * the resulting queue is applied. An explicit queue always wins; an unrouted
 * job falls through to the driver default.
 *
 * It wraps whatever connection is bound to `flarum.queue.connection` (core's or
 * a replacement from FoF Redis / Horizon), so routing is driver-independent.
 * Everything other than the routing decision is delegated to the wrapped
 * connection, including methods beyond the Queue contract (via `__call`); the
 * underlying driver is available through {@see getDriver()} for the few callers
 * that need the concrete instance.
 */
class RoutingQueue implements Queue
{
    public function __construct(
        protected Queue $driver,
        protected QueueRoutes $routes
    ) {
    }

    /**
     * The wrapped driver connection.
     */
    public function getDriver(): Queue
    {
        return $this->driver;
    }

    /**
     * Resolve the queue for a job: an explicit queue wins; otherwise the route
     * registered for the job's class (or an ancestor); otherwise null (driver
     * default). Only job objects can be routed — string job names pass through.
     *
     * @param mixed $job
     * @param string|null $queue
     */
    protected function routeFor($job, $queue): ?string
    {
        if ($queue !== null || ! is_object($job)) {
            return $queue;
        }

        return $this->routes->getQueue($job);
    }

    public function push($job, $data = '', $queue = null)
    {
        return $this->driver->push($job, $data, $this->routeFor($job, $queue));
    }

    public function later($delay, $job, $data = '', $queue = null)
    {
        return $this->driver->later($delay, $job, $data, $this->routeFor($job, $queue));
    }

    public function bulk($jobs, $data = '', $queue = null)
    {
        // A bulk push shares one queue for the batch; only route it when the
        // caller gave no queue and every job resolves to the same route.
        if ($queue === null) {
            $resolved = [];

            foreach ((array) $jobs as $job) {
                $resolved[] = is_object($job) ? $this->routes->getQueue($job) : null;
            }

            $unique = array_values(array_unique($resolved, SORT_REGULAR));

            if (count($unique) === 1 && $unique[0] !== null) {
                $queue = $unique[0];
            }
        }

        return $this->driver->bulk($jobs, $data, $queue);
    }

    public function pushOn($queue, $job, $data = '')
    {
        return $this->driver->pushOn($queue, $job, $data);
    }

    public function laterOn($queue, $delay, $job, $data = '')
    {
        return $this->driver->laterOn($queue, $delay, $job, $data);
    }

    public function pushRaw($payload, $queue = null, array $options = [])
    {
        return $this->driver->pushRaw($payload, $queue, $options);
    }

    public function pop($queue = null)
    {
        return $this->driver->pop($queue);
    }

    public function size($queue = null)
    {
        return $this->driver->size($queue);
    }

    public function pendingSize($queue = null)
    {
        return $this->driver->pendingSize($queue);
    }

    public function delayedSize($queue = null)
    {
        return $this->driver->delayedSize($queue);
    }

    public function reservedSize($queue = null)
    {
        return $this->driver->reservedSize($queue);
    }

    public function creationTimeOfOldestPendingJob($queue = null)
    {
        return $this->driver->creationTimeOfOldestPendingJob($queue);
    }

    public function getConnectionName()
    {
        return $this->driver->getConnectionName();
    }

    public function setConnectionName($name)
    {
        $this->driver->setConnectionName($name);

        return $this;
    }

    /**
     * Delegate any method beyond the Queue contract to the wrapped driver
     * (drivers expose extra methods — setContainer(), getConnectionConfig(),
     * etc. — that callers rely on).
     *
     * @param string            $name
     * @param array<int, mixed> $arguments
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return $this->driver->$name(...$arguments);
    }
}
