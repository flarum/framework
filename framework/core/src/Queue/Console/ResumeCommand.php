<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Queue\Console;

use Flarum\Queue\QueueFactory;
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
    protected $signature = 'queue:resume {queue? : The queue to resume, optionally prefixed with a connection (connection:queue). Omit to resume all paused queues} {--all : Resume all queues on the connection}';

    protected $description = 'Resume processing of jobs on a paused queue';

    public function handle(Factory $factory, Queue $connection): int
    {
        $connectionName = $connection->getConnectionName();

        // A bare `queue:resume` (and --all) means "get jobs flowing again":
        // clear the wildcard pause and every individually paused queue.
        // Otherwise the admin footgun returns — "pause all" sets the wildcard
        // key, which a resume targeting only `default` would silently leave in
        // place.
        if (! $this->argument('queue')) {
            $paused = $factory instanceof QueueFactory ? $factory->pausedQueues($connectionName) : [];

            // Also clear every known queue name, not only the tracking list.
            // A queue paused through a path that never recorded the tracking
            // entry (e.g. the historical queue:pause crash on an unnamed
            // connection) would otherwise be left paused with no way for
            // "resume everything" to reach it.
            $known = (array) $this->laravel->make('flarum.queue.queues');

            foreach (array_unique(array_merge(['*'], $paused, $known)) as $queue) {
                $factory->resume($connectionName, $queue);
            }

            $this->components->info("Job processing on connection [{$connectionName}] has been resumed.");

            return 0;
        }

        [$connectionName, $queue] = $this->option('all')
            ? [$connectionName, '*']
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
