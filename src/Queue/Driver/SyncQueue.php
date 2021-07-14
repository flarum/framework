<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Queue\Driver;

use Illuminate\Contracts\Queue\Queue;

class SyncQueue extends Driver
{
    public function build(): Queue
    {
        $queue = new \Illuminate\Queue\SyncQueue();

        $queue->setContainer($this->container);

        return $queue;
    }
}
