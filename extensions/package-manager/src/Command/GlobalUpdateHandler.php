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
use Illuminate\Contracts\Events\Dispatcher;
use Flarum\PackageManager\Event\FlarumUpdated;
use Flarum\PackageManager\Exception\ComposerUpdateFailedException;
use Symfony\Component\Console\Input\StringInput;

class GlobalUpdateHandler
{
    /**
     * @var ComposerAdapter
     */
    protected $composer;

    /**
     * @var Dispatcher
     */
    protected $events;

    /**
     * @var FlarumDispatcher
     */
    protected $commandDispatcher;

    public function __construct(ComposerAdapter $composer, Dispatcher $events, FlarumDispatcher $commandDispatcher)
    {
        $this->composer = $composer;
        $this->events = $events;
        $this->commandDispatcher = $commandDispatcher;
    }

    /**
     * @throws \Flarum\User\Exception\PermissionDeniedException|ComposerUpdateFailedException
     */
    public function handle(GlobalUpdate $command)
    {
        $command->actor->assertAdmin();

        $output = $this->composer->run(
            new StringInput("update --prefer-dist --no-dev -a --with-all-dependencies")
        );

        if ($output->getExitCode() !== 0) {
            throw new ComposerUpdateFailedException('*', $output->getContents());
        }

        $this->commandDispatcher->dispatch(
            new CheckForUpdates($command->actor)
        );

        $this->events->dispatch(
            new FlarumUpdated(FlarumUpdated::GLOBAL)
        );

        return true;
    }
}
