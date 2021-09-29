<?php

/**
 *
 */

namespace SychO\PackageManager\Command;

use Carbon\Carbon;
use Composer\Console\Application;
use Flarum\Settings\SettingsRepositoryInterface;
use SychO\PackageManager\Exception\ComposerCommandFailedException;
use SychO\PackageManager\LastUpdateCheck;
use SychO\PackageManager\OutputLogger;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class CheckForUpdatesHandler
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
     * @var OutputLogger
     */
    protected $logger;

    public function __construct(Application $composer, LastUpdateCheck $lastUpdateCheck, OutputLogger $logger)
    {
        $this->composer = $composer;
        $this->lastUpdateCheck = $lastUpdateCheck;
        $this->logger = $logger;
    }

    /**
     * @throws \Flarum\User\Exception\PermissionDeniedException
     * @throws ComposerCommandFailedException
     */
    public function handle(CheckForUpdates $command)
    {
        $actor = $command->actor;

        $actor->assertAdmin();

        $output = new BufferedOutput();
        $input = new ArrayInput([
            'command' => 'outdated',
            '-D' => true,
            '--format' => 'json',
        ]);

        $exitCode = $this->composer->run($input, $output);
        $output = $output->fetch();

        $this->logger->log($output, $exitCode);

        if ($exitCode !== 0) {
            throw new ComposerCommandFailedException('', $output);
        }

        return $this->lastUpdateCheck->save(json_decode($output, true));
    }
}
