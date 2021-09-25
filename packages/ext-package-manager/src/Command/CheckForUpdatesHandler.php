<?php

/**
 *
 */

namespace SychO\PackageManager\Command;

use Carbon\Carbon;
use Composer\Console\Application;
use Flarum\Settings\SettingsRepositoryInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class CheckForUpdatesHandler
{
    /**
     * @var Application
     */
    protected $composer;

    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @param Application $composer
     * @param SettingsRepositoryInterface $settings
     */
    public function __construct(Application $composer, SettingsRepositoryInterface $settings)
    {
        $this->composer = $composer;
        $this->settings = $settings;
    }

    /**
     * @throws \Flarum\User\Exception\PermissionDeniedException
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

        $this->composer->run($input, $output);

        $lastUpdateCheck = [
            'checkedAt' => Carbon::now(),
            'updates' => json_decode($output->fetch(), true),
        ];

        $this->settings->set('sycho-package-manager.last_update_check', json_encode($lastUpdateCheck));

        return $lastUpdateCheck;
    }
}
