<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Queue;

use Closure;
use Illuminate\Contracts\Queue\Factory;
use Illuminate\Contracts\Queue\Queue;

class QueueFactory implements Factory
{
    /**
     * The cached queue instance.
     */
    private ?Queue $queue = null;

    /**
     * Expects a callback that will be called to instantiate the queue adapter,
     * once requested by the application.
     */
    public function __construct(
        private readonly Closure $factory
    ) {
    }

    /**
     * Resolve a queue connection instance.
     *
     * @param string $name
     * @return Queue
     */
    public function connection($name = null)
    {
        if (is_null($this->queue)) {
            $this->queue = ($this->factory)();
        }

        return $this->queue;
    }

    /*
     * Flarum's simplified queue factory stands in for Illuminate's full
     * QueueManager. The methods below are the manager-level surface of the
     * Queue Pause/Resume feature, which the queue Worker and console commands
     * may call. Flarum does not support queue pausing *yet* — it is planned for
     * a future Flarum version — so for now they are no-ops. Stubbing the whole
     * family also means a new Illuminate release wiring up another pause method
     * can't crash the worker with a "Call to undefined method" (as happened
     * with isPaused() and, later, getPausedQueues()). Signatures mirror
     * Illuminate\Queue\QueueManager.
     *
     * TODO: implement real queue pausing in a future Flarum version.
     */

    /**
     * Determine if a queue is paused.
     *
     * @param string $connection
     * @param string $queue
     * @return bool
     */
    public function isPaused($connection, $queue): bool
    {
        return false;
    }

    /**
     * Determine which of the given queues are currently paused.
     *
     * @param string $connection
     * @param array $queues
     * @return array
     */
    public function getPausedQueues($connection, $queues): array
    {
        return [];
    }

    /**
     * Pause a queue by its connection and name.
     *
     * @param string $connection
     * @param string $queue
     * @return void
     */
    public function pause($connection, $queue): void
    {
        // No-op for now: queue pausing is planned for a future Flarum version.
    }

    /**
     * Pause a queue by its connection and name for a given amount of time.
     *
     * @param string $connection
     * @param string $queue
     * @param \DateTimeInterface|\DateInterval|int $ttl
     * @return void
     */
    public function pauseFor($connection, $queue, $ttl): void
    {
        // No-op for now: queue pausing is planned for a future Flarum version.
    }

    /**
     * Resume a paused queue by its connection and name.
     *
     * @param string $connection
     * @param string $queue
     * @return void
     */
    public function resume($connection, $queue): void
    {
        // No-op for now: queue pausing is planned for a future Flarum version.
    }

    /**
     * Indicate that queue workers should not poll for restart or pause signals.
     *
     * @return void
     */
    public function withoutInterruptionPolling(): void
    {
        // No-op for now: queue pausing is planned for a future Flarum version.
    }
}
