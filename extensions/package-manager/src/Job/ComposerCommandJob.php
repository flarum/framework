<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager\Job;

use Flarum\Bus\Dispatcher;
use Flarum\PackageManager\Command\BusinessCommandInterface;
use Flarum\Queue\AbstractJob;
use Throwable;

class ComposerCommandJob extends AbstractJob
{
    /**
     * @var BusinessCommandInterface
     */
    protected $command;

    public function __construct(BusinessCommandInterface $command)
    {
        $this->command = $command;
    }

    public function handle(Dispatcher $bus)
    {
        try {

            $this->command->task->start();

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

        $this->command->task->end(false);

        $this->fail($exception);
    }
}
