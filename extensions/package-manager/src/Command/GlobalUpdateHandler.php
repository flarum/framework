<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager\Command;

use Composer\Console\Application;
use Flarum\Bus\Dispatcher as FlarumDispatcher;
use Illuminate\Contracts\Events\Dispatcher;
use Flarum\PackageManager\Event\FlarumUpdated;
use Flarum\PackageManager\Exception\ComposerUpdateFailedException;
use Flarum\PackageManager\OutputLogger;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class GlobalUpdateHandler
{
    /**
     * @var Application
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

    /**
     * @var OutputLogger
     */
    protected $logger;

    public function __construct(Application $composer, Dispatcher $events, FlarumDispatcher $commandDispatcher, OutputLogger $logger)
    {
        $this->composer = $composer;
        $this->events = $events;
        $this->commandDispatcher = $commandDispatcher;
        $this->logger = $logger;
    }

    /**
     * @throws \Flarum\User\Exception\PermissionDeniedException|ComposerUpdateFailedException
     */
    public function handle(GlobalUpdate $command)
    {
        $command->actor->assertAdmin();

        $output = new BufferedOutput();
        $input = new ArrayInput([
            'command' => 'update',
            '--prefer-dist' => true,
            '--no-dev' => true,
            '-a' => true,
            '--with-all-dependencies' => true,
        ]);

        $exitCode = $this->composer->run($input, $output);
        $output = $output->fetch();

        $this->logger->log($input->__toString(), $output, $exitCode);

        if ($exitCode !== 0) {
            throw new ComposerUpdateFailedException('*', $output);
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
