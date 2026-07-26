<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Queue;

use Closure;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\Factory;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Queue\Events\QueuePaused;
use Illuminate\Queue\Events\QueueResumed;
use Illuminate\Queue\Worker;

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
        private readonly Closure $factory,
        private readonly ?Cache $cache = null,
        private readonly ?Dispatcher $events = null,
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
     * Queue Pause/Resume feature, which the queue Worker consults when
     * popping jobs. The pause state lives in the shared cache store using
     * Illuminate's own key format, so the signal reaches every worker
     * process — including ones in other containers, and Horizon workers,
     * which resolve this same factory. Signatures mirror
     * Illuminate\Queue\QueueManager.
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
        if ($this->cache?->get("illuminate:queue:paused:{$connection}:{$queue}", false)) {
            return true;
        }

        // A wildcard pause covers every queue on the connection — including
        // names core cannot enumerate — so "pause all" works regardless of
        // the queue backend in use.
        return $queue !== '*' && (bool) $this->cache?->get("illuminate:queue:paused:{$connection}:*", false);
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
        return array_values(array_filter(
            $queues,
            fn ($queue) => $this->isPaused($connection, $queue)
        ));
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
        $this->cache?->forever("illuminate:queue:paused:{$connection}:{$queue}", true);

        $this->trackPausedQueue($connection, $queue);

        $this->events?->dispatch(new QueuePaused($connection, $queue));
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
        $this->cache?->put("illuminate:queue:paused:{$connection}:{$queue}", true, $ttl);

        $this->trackPausedQueue($connection, $queue);

        $this->events?->dispatch(new QueuePaused($connection, $queue, $ttl));
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
        $this->cache?->forget("illuminate:queue:paused:{$connection}:{$queue}");

        $this->untrackPausedQueue($connection, $queue);

        $this->events?->dispatch(new QueueResumed($connection, $queue));
    }

    /**
     * The names of the queues currently paused on the given connection.
     *
     * This is a Flarum addition over Illuminate's surface, powering the admin
     * dashboard warning. Pauses are tracked when made through this factory;
     * entries whose underlying pause has expired (pauseFor) or was cleared
     * externally are filtered out.
     *
     * @return string[]
     */
    public function pausedQueues(string $connection): array
    {
        $tracked = (array) ($this->cache?->get("flarum:queue:paused-list:{$connection}") ?? []);

        $paused = $this->getPausedQueues($connection, $tracked);

        if ($paused !== $tracked) {
            $this->cache?->forever("flarum:queue:paused-list:{$connection}", $paused);
        }

        return $paused;
    }

    protected function trackPausedQueue(string $connection, string $queue): void
    {
        $tracked = (array) ($this->cache?->get("flarum:queue:paused-list:{$connection}") ?? []);

        if (! in_array($queue, $tracked, true)) {
            $tracked[] = $queue;
            $this->cache?->forever("flarum:queue:paused-list:{$connection}", $tracked);
        }
    }

    protected function untrackPausedQueue(string $connection, string $queue): void
    {
        $tracked = (array) ($this->cache?->get("flarum:queue:paused-list:{$connection}") ?? []);

        $remaining = array_values(array_diff($tracked, [$queue]));

        if ($remaining !== $tracked) {
            $this->cache?->forever("flarum:queue:paused-list:{$connection}", $remaining);
        }
    }

    /**
     * Indicate that queue workers should not poll for restart or pause signals.
     *
     * @return void
     */
    public function withoutInterruptionPolling(): void
    {
        Worker::$restartable = false;
        Worker::$pausable = false;
    }
}
