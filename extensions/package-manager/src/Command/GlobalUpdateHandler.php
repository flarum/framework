<?php

/**
 *
 */

namespace SychO\PackageManager\Command;

use Composer\Console\Application;
use Flarum\Bus\Dispatcher as FlarumDispatcher;
use Illuminate\Contracts\Events\Dispatcher;
use SychO\PackageManager\Event\FlarumUpdated;
use SychO\PackageManager\Exception\ComposerUpdateFailedException;
use SychO\PackageManager\OutputLogger;
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

        $this->logger->log($output->fetch(), $exitCode);

        if ($exitCode !== 0) {
            throw new ComposerUpdateFailedException('*', $output->fetch());
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
