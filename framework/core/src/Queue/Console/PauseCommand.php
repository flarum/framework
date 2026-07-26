<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Queue\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\Factory;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Queue\Worker;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Flarum's counterpart to Illuminate's queue:pause: the upstream command
 * type-hints the concrete QueueManager, which Flarum replaces with its own
 * factory. The queue argument accepts `queue` or `connection:queue`; the
 * connection defaults to the active queue connection's name.
 */
#[AsCommand(name: 'queue:pause')]
class PauseCommand extends Command
{
    protected $signature = 'queue:pause {queue=default : The name of the queue to pause, optionally prefixed with a connection (connection:queue)} {--all : Pause all queues on the connection}';

    protected $description = 'Pause processing of jobs on a queue';

    public function handle(Factory $factory, Queue $connection): int
    {
        if (! Worker::$pausable) {
            $this->components->error('Queue pausing is currently disabled.');

            return 1;
        }

        [$connectionName, $queue] = $this->option('all')
            ? [$connection->getConnectionName(), '*']
            : $this->parseQueue($this->argument('queue'), $connection);

        $factory->pause($connectionName, $queue);

        $this->components->info("Job processing on queue [{$connectionName}:{$queue}] has been paused.");

        return 0;
    }

    /**
     * @return array{string, string}
     */
    protected function parseQueue(string $argument, Queue $connection): array
    {
        if (str_contains($argument, ':')) {
            return explode(':', $argument, 2);
        }

        return [$connection->getConnectionName(), $argument];
    }
}
