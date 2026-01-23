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

    /**
     * Determine if a queue is paused.
     *
     * This is a no-op implementation since Flarum's simplified queue factory
     * doesn't support queue pausing. Laravel 12's Worker expects this method
     * to exist on the queue manager.
     *
     * @param string $connection
     * @param string $queue
     * @return bool
     */
    public function isPaused($connection, $queue)
    {
        return false;
    }
}
