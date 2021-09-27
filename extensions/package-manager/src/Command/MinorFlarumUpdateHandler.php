<?php

/**
 *
 */

namespace SychO\PackageManager\Command;

use Composer\Console\Application;
use SychO\PackageManager\Exception\ComposerUpdateFailedException;
use SychO\PackageManager\LastUpdateCheck;
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
     * @param Application $composer
     * @param LastUpdateCheck $lastUpdateCheck
     */
    public function __construct(Application $composer, LastUpdateCheck $lastUpdateCheck)
    {
        $this->composer = $composer;
        $this->lastUpdateCheck = $lastUpdateCheck;
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

        if ($exitCode !== 0) {
            throw new ComposerUpdateFailedException('flarum/*', $output->fetch());
        }

        $this->lastUpdateCheck->forget('flarum/*', true);

        return true;
    }
}
