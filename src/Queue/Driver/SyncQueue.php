<?php

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
