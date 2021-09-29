<?php

/**
 *
 */

namespace SychO\PackageManager\Command;

use Composer\Console\Application;
use Illuminate\Contracts\Events\Dispatcher;
use SychO\PackageManager\Event\FlarumUpdated;
use SychO\PackageManager\Exception\ComposerUpdateFailedException;
use SychO\PackageManager\LastUpdateCheck;
use SychO\PackageManager\OutputLogger;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class MinorFlarumUpdateHandler
{
    /**
     * @var Application
     */
    protected $composer;

    /**
     * @var LastUpdateCheck
     */
    protected $lastUpdateCheck;

    /**
     * @var Dispatcher
     */
    protected $events;

    /**
     * @var OutputLogger
     */
    protected $logger;

    public function __construct(Application $composer, LastUpdateCheck $lastUpdateCheck, Dispatcher $events, OutputLogger $logger)
    {
        $this->composer = $composer;
        $this->lastUpdateCheck = $lastUpdateCheck;
        $this->events = $events;
        $this->logger = $logger;
    }

    /**
     * @throws \Flarum\User\Exception\PermissionDeniedException
     * @throws ComposerUpdateFailedException
     */
    public function handle(MinorFlarumUpdate $command)
    {
        $command->actor->assertAdmin();

        $output = new BufferedOutput();
        $input = new ArrayInput([
            'command' => 'update',
            'packages' => ["flarum/*"],
            '--prefer-dist' => true,
            '--no-dev' => true,
            '-a' => true,
            '--with-all-dependencies' => true,
        ]);

        $exitCode = $this->composer->run($input, $output);

        $this->logger->log($output->fetch(), $exitCode);

        if ($exitCode !== 0) {
            throw new ComposerUpdateFailedException('flarum/*', $output->fetch());
        }

        $this->lastUpdateCheck->forget('flarum/*', true);

        $this->events->dispatch(
            new FlarumUpdated(FlarumUpdated::MINOR)
        );

        return true;
    }
}
