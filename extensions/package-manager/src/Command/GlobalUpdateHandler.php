<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager\Command;

use Flarum\Bus\Dispatcher as FlarumDispatcher;
use Flarum\PackageManager\Composer\ComposerAdapter;
use Flarum\PackageManager\Event\FlarumUpdated;
use Flarum\PackageManager\Exception\ComposerUpdateFailedException;
use Illuminate\Contracts\Events\Dispatcher;
use Symfony\Component\Console\Input\StringInput;

class GlobalUpdateHandler
{
    public function __construct(
        protected ComposerAdapter $composer,
        protected Dispatcher $events,
        protected FlarumDispatcher $commandDispatcher
    ) {
    }

    public function handle(GlobalUpdate $command): void
    {
        $command->actor->assertAdmin();

        $output = $this->composer->run(
            new StringInput('update --prefer-dist --no-dev -a --with-all-dependencies'),
            $command->task ?? null
        );

        if ($output->getExitCode() !== 0) {
            throw new ComposerUpdateFailedException('*', $output->getContents());
        }

        $this->events->dispatch(
            new FlarumUpdated($command->actor, FlarumUpdated::GLOBAL)
        );
    }
}
