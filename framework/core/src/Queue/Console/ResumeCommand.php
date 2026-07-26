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
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Flarum's counterpart to Illuminate's queue:resume — see PauseCommand for
 * why the upstream commands cannot be reused directly.
 */
#[AsCommand(name: 'queue:resume')]
class ResumeCommand extends Command
{
    protected $signature = 'queue:resume {queue=default : The name of the queue to resume, optionally prefixed with a connection (connection:queue)} {--all : Resume all queues on the connection}';

    protected $description = 'Resume processing of jobs on a paused queue';

    public function handle(Factory $factory, Queue $connection): int
    {
        [$connectionName, $queue] = $this->option('all')
            ? [$connection->getConnectionName(), '*']
            : $this->parseQueue($this->argument('queue'), $connection);

        $factory->resume($connectionName, $queue);

        $this->components->info("Job processing on queue [{$connectionName}:{$queue}] has been resumed.");

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
