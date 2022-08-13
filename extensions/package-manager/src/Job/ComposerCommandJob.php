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

    /**
     * @var int[]
     */
    protected $phpVersion;

    public function __construct(BusinessCommandInterface $command, array $phpVersion)
    {
        $this->command = $command;
        $this->phpVersion = $phpVersion;
    }

    public function handle(Dispatcher $bus)
    {
        try {
            if ([PHP_MAJOR_VERSION, PHP_MINOR_VERSION] !== [$this->phpVersion[0], $this->phpVersion[1]]) {
                $webPhpVersion = implode('.', $this->phpVersion);
                $sshPhpVersion = implode('.', [PHP_MAJOR_VERSION, PHP_MINOR_VERSION]);

                throw new \Exception("PHP version mismatch. SSH PHP version must match web server PHP version. Found SSH (PHP $sshPhpVersion) and Web Server (PHP $webPhpVersion).");
            }

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
