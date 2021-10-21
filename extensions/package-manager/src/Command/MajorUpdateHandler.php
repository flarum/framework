<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager\Command;

use Composer\Console\Application;
use Flarum\Foundation\Paths;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Arr;
use Flarum\PackageManager\Event\FlarumUpdated;
use Flarum\PackageManager\Exception\ComposerUpdateFailedException;
use Flarum\PackageManager\LastUpdateCheck;
use Flarum\PackageManager\OutputLogger;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class MajorUpdateHandler
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

    /**
     * @var Paths
     */
    protected $paths;

    /**
     * @var array
     */
    protected $composerJson;

    public function __construct(Application $composer, LastUpdateCheck $lastUpdateCheck, Dispatcher $events, OutputLogger $logger, Paths $paths)
    {
        $this->composer = $composer;
        $this->lastUpdateCheck = $lastUpdateCheck;
        $this->events = $events;
        $this->logger = $logger;
        $this->paths = $paths;
    }

    /**
     * Set the version constraint for all directly required packages in the root composer.json to *.
     * Set flarum/core version constraint to new major version.
     * Run composer update --prefer-dist --no-plugins --no-dev -a --with-all-dependencies.
     * Clear cache.
     * Run migrations.
     *
     * @throws \Flarum\User\Exception\PermissionDeniedException
     * @throws ComposerUpdateFailedException
     */
    public function handle(MajorUpdate $command)
    {
        $command->actor->assertAdmin();

        $majorVersion = $this->getNewMajorVersion();

        if (! $majorVersion) {
            return false;
        }

        $this->updateComposerJson($majorVersion);

        $this->runCommand($command->dryRun);

        if ($command->dryRun) {
            $this->revertComposerJson();

            return true;
        }

        $this->lastUpdateCheck->forget('flarum/*', true);

        $this->events->dispatch(
            new FlarumUpdated(FlarumUpdated::MAJOR)
        );

        return true;
    }

    protected function getNewMajorVersion(): ?string
    {
        $core = Arr::first($this->lastUpdateCheck->get()['updates']['installed'], function ($package) {
            return $package['name'] === 'flarum/core';
        });

        return $core ? $core['latest-major'] : null;
    }

    protected function updateComposerJson(string $majorVersion): void
    {
        $composerJsonPath = $this->paths->base . '/composer.json';
        $this->composerJson = $newComposerJson = json_decode(file_get_contents($composerJsonPath), true);

        foreach ($newComposerJson['require'] as $name => &$version) {
            if ($name === 'flarum/core') {
                $version = '^'.str_replace('v', '', $majorVersion);
            } else {
                $version = '*';
            }
        }

        file_put_contents($composerJsonPath, json_encode($newComposerJson));
    }

    protected function revertComposerJson(): void
    {
        $composerJsonPath = $this->paths->base . '/composer.json';
        file_put_contents($composerJsonPath, $this->composerJson);
    }

    /**
     * @throws ComposerUpdateFailedException
     */
    protected function runCommand(bool $dryRun): void
    {
        $output = new BufferedOutput();
        $input = new ArrayInput([
            'command' => 'update',
            '--prefer-dist' => true,
            '--no-plugins' => true,
            '--no-dev' => true,
            '-a' => true,
            '--with-all-dependencies' => true,
            '--dry-run' => $dryRun,
        ]);

        $exitCode = $this->composer->run($input, $output);
        $output = $output->fetch();

        $this->logger->log($input->__toString(), $output, $exitCode);

        if ($exitCode !== 0) {
            throw new ComposerUpdateFailedException('*', $output);
        }
    }
}
