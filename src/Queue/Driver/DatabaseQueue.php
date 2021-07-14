<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Queue\Driver;

use Illuminate\Contracts\Queue\Queue;
use Illuminate\Database\ConnectionInterface;

class DatabaseQueue extends Driver
{
    public function build(): Queue
    {
        $queue = new \Illuminate\Queue\DatabaseQueue(
            $this->container->make(ConnectionInterface::class),
            'queue_jobs'
        );

        $queue->setContainer($this->container);

        return $queue;
    }
}
