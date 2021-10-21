<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager\Command;

use Composer\Console\Application;
use Flarum\PackageManager\Exception\ComposerCommandFailedException;
use Flarum\PackageManager\LastUpdateCheck;
use Flarum\PackageManager\OutputLogger;
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
     * We run two commands here
     *
     * `composer outdated -D --format json`
     * This queries latest versions for all direct packages, so it can include major updates,
     * that are not necessarily compatible with the current flarum version.
     * That includes flarum/core itself, so for example if we are on flarum/core v1.8.0
     * and there are v1.8.1 and v2.0.0 available, the command would only let us know of v2.0.0.
     *
     * `composer outdated -D --minor-only --format json`
     * This only lists latest minor updates, we need to run this as well not only to be able to know
     * of these minor versions in addition to major ones, but especially for the flarum/core, as explained above
     * we need to know of minor core updates, even if there is a major version available.
     *
     * The results from both commands are properly processed and merged to have new key values `latest-minor` and `latest-major`.
     *
     * @throws \Flarum\User\Exception\PermissionDeniedException|ComposerCommandFailedException
     */
    public function handle(CheckForUpdates $command)
    {
        $actor = $command->actor;

        $actor->assertAdmin();

        $firstOutput = $this->runComposerCommand(false);
        $firstOutput = json_decode($firstOutput, true);

        $majorUpdates = false;

        foreach ($firstOutput['installed'] as $package) {
            if ($package['latest-status'] === 'update-possible') {
                $majorUpdates = true;
                break;
            }
        }

        if ($majorUpdates) {
            $secondOutput = $this->runComposerCommand(true);
            $secondOutput = json_decode($secondOutput, true);
        } else {
            $secondOutput = ['installed' => []];
        }

        foreach ($firstOutput['installed'] as &$mainPackageUpdate) {
            $mainPackageUpdate['latest-minor'] = $mainPackageUpdate['latest-major'] = null;

            if ($mainPackageUpdate['latest-status'] === 'update-possible') {
                $mainPackageUpdate['latest-major'] = $mainPackageUpdate['latest'];

                $minorPackageUpdate = array_filter($secondOutput['installed'], function ($package) use ($mainPackageUpdate) {
                    return $package['name'] === $mainPackageUpdate['name'];
                })[0] ?? null;

                if ($minorPackageUpdate) {
                    $mainPackageUpdate['latest-minor'] = $minorPackageUpdate['latest'];
                }
            } else {
                $mainPackageUpdate['latest-minor'] = $mainPackageUpdate['latest'];
            }
        }

        return $this->lastUpdateCheck->save($firstOutput);
    }

    /**
     * @throws ComposerCommandFailedException
     */
    protected function runComposerCommand(bool $minorOnly): string
    {
        $output = new BufferedOutput();
        $input = new ArrayInput([
            'command' => 'outdated',
            '-D' => true,
            '--minor-only' => $minorOnly,
            '--format' => 'json',
        ]);

        $exitCode = $this->composer->run($input, $output);
        $output = $output->fetch();

        $this->logger->log($input->__toString(), $output, $exitCode);

        if ($exitCode !== 0) {
            throw new ComposerCommandFailedException('', $output);
        }

        return $output;
    }
}
