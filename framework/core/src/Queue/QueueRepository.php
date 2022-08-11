<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Queue;

use Illuminate\Contracts\Queue\Queue;
use Illuminate\Support\Str;

class QueueRepository
{
    /**
     * Identify the queue driver in use.
     *
     * @param Queue $queue
     * @return string
     */
    public function identifyDriver(Queue $queue): string
    {
        // Get class name
        $queue = get_class($queue);
        // Drop the namespace
        $queue = Str::afterLast($queue, '\\');
        // Lowercase the class name
        $queue = strtolower($queue);
        // Drop everything like queue SyncQueue, RedisQueue
        $queue = str_replace('queue', '', $queue);

        return $queue;
    }
}
