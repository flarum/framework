<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager\Command;

use Flarum\Foundation\Paths;
use Flarum\PackageManager\Composer\ComposerAdapter;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Arr;
use Flarum\PackageManager\Event\FlarumUpdated;
use Flarum\PackageManager\Exception\ComposerUpdateFailedException;
use Flarum\PackageManager\LastUpdateCheck;
use Symfony\Component\Console\Input\ArrayInput;

class MajorUpdateHandler
{
    /**
     * @var ComposerAdapter
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
     * @var Paths
     */
    protected $paths;

    /**
     * @var array
     */
    protected $composerJson;

    public function __construct(ComposerAdapter $composer, LastUpdateCheck $lastUpdateCheck, Dispatcher $events, Paths $paths)
    {
        $this->composer = $composer;
        $this->lastUpdateCheck = $lastUpdateCheck;
        $this->events = $events;
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
        // @todo use filesystem for all file_get_contents
        file_put_contents($composerJsonPath, $this->composerJson);
    }

    /**
     * @throws ComposerUpdateFailedException
     */
    protected function runCommand(bool $dryRun): void
    {
        $output = $this->composer->run(
            new ArrayInput([
                'command' => 'update',
                '--prefer-dist' => true,
                '--no-plugins' => true,
                '--no-dev' => true,
                '-a' => true,
                '--with-all-dependencies' => true,
                '--dry-run' => $dryRun,
            ])
        );

        if ($output->getExitCode() !== 0) {
            throw new ComposerUpdateFailedException('*', $output->getContents());
        }
    }
}
