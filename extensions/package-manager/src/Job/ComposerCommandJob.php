<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Job;

use Flarum\Bus\Dispatcher;
use Flarum\ExtensionManager\Command\AbstractActionCommand;
use Flarum\ExtensionManager\Composer\ComposerAdapter;
use Flarum\ExtensionManager\Exception\ComposerCommandFailedException;
use Flarum\Queue\AbstractJob;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Throwable;

class ComposerCommandJob extends AbstractJob implements ShouldBeUnique
{
    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 60 * 3;

    /**
     * Composer commands are not idempotent; a retry after OOM (which bypasses
     * the handle() try/catch) could run against a partially-modified vendor/.
     */
    public int $tries = 1;

    /**
     * How long (seconds) the ShouldBeUnique lock is held.
     *
     * Without an explicit value the default is 0, meaning the lock never
     * expires. If the worker crashes mid-install, the lock can otherwise
     * block future dispatches until the cache TTL runs down. 600s is
     * longer than $timeout above so it won't expire mid-run.
     */
    public int $uniqueFor = 600;

    public function __construct(
        protected AbstractActionCommand $command,
        protected string $phpVersion
    ) {
    }

    public function handle(Dispatcher $bus): void
    {
        try {
            $this->command->task->start();

            ComposerAdapter::setPhpVersion($this->phpVersion);

            $bus->dispatch($this->command);

            $this->command->task->end(true);
        } catch (Throwable $exception) {
            $this->abort($exception);
        }
    }

    public function abort(Throwable $exception): void
    {
        if (empty($this->command->task->output)) {
            $this->command->task->output = $exception->getMessage();
        }

        if ($exception instanceof ComposerCommandFailedException) {
            $this->command->task->guessed_cause = $exception->guessCause();
        }

        $this->command->task->end(false);
    }

    public function failed(Throwable $exception): void
    {
        $this->abort($exception);
    }

    public function middleware(): array
    {
        return [
            // expireAfter matches $uniqueFor above so a crashed worker
            // can't leave the overlap lock permanently held (default 0
            // would mean the lock never expires).
            (new WithoutOverlapping())->expireAfter(600),
        ];
    }
}
