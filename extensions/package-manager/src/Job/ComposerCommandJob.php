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
use Flarum\PackageManager\Exception\ComposerCommandFailedException;
use Flarum\Queue\AbstractJob;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Throwable;

class ComposerCommandJob extends AbstractJob implements ShouldBeUnique
{
    /**
     * @var AbstractActionCommand
     */
    protected $command;

    /**
     * @var string
     */
    protected $phpVersion;

    public function __construct(AbstractActionCommand $command, string $phpVersion)
    {
        $this->command = $command;
        $this->phpVersion = $phpVersion;
    }

    public function handle(Dispatcher $bus)
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

    public function abort(Throwable $exception)
    {
        if (! $this->command->task->output) {
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
            new WithoutOverlapping(),
        ];
    }
}
