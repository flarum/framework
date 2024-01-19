<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager\Job;

use Flarum\Bus\Dispatcher;
use Flarum\PackageManager\Command\AbstractActionCommand;
use Flarum\PackageManager\Composer\ComposerAdapter;
use Flarum\Queue\AbstractJob;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Throwable;

class ComposerCommandJob extends AbstractJob
{
    public function __construct(
        protected AbstractActionCommand $command,
        protected string $phpVersion
    ) {
    }

    public function handle(Dispatcher $bus): void
    {
        try {
            ComposerAdapter::setPhpVersion($this->phpVersion);

            $this->command->task->start();

            $bus->dispatch($this->command);

            $this->command->task->end(true);
        } catch (Throwable $exception) {
            $this->abort($exception);
        }
    }

    public function abort(Throwable $exception): void
    {
        if (! $this->command->task->output) {
            $this->command->task->output = $exception->getMessage();
        }

        $this->command->task->end(false);

        $this->fail($exception);
    }

    public function middleware(): array
    {
        return [
            new WithoutOverlapping(),
        ];
    }
}
