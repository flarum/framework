<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager\Command;

use Flarum\PackageManager\Composer\ComposerAdapter;
use Flarum\PackageManager\Composer\ComposerJson;
use Flarum\PackageManager\Exception\MajorUpdateFailedException;
use Flarum\PackageManager\Exception\NoNewMajorVersionException;
use Illuminate\Contracts\Events\Dispatcher;
use Flarum\PackageManager\Event\FlarumUpdated;
use Flarum\PackageManager\Settings\LastUpdateCheck;
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
     * @var ComposerJson
     */
    protected $composerJson;

    /**
     * @param ComposerAdapter $composer
     * @param LastUpdateCheck $lastUpdateCheck
     * @param Dispatcher $events
     * @param ComposerJson $composerJson
     */
    public function __construct(ComposerAdapter $composer, LastUpdateCheck $lastUpdateCheck, Dispatcher $events, ComposerJson $composerJson)
    {
        $this->composer = $composer;
        $this->lastUpdateCheck = $lastUpdateCheck;
        $this->events = $events;
        $this->composerJson = $composerJson;
    }

    /**
     * Set the version constraint for all directly required packages in the root composer.json to *.
     * Set flarum/core version constraint to new major version.
     * Run composer update --prefer-dist --no-plugins --no-dev -a --with-all-dependencies.
     * Clear cache.
     * Run migrations.
     *
     * @throws \Flarum\User\Exception\PermissionDeniedException
     * @throws NoNewMajorVersionException|MajorUpdateFailedException
     */
    public function handle(MajorUpdate $command)
    {
        $command->actor->assertAdmin();

        // @todo remove testing code
        throw new MajorUpdateFailedException(
            '*',
            'Loading composer repositories with package information
Updating dependencies
Your requirements could not be resolved to an installable set of packages.

  Problem 1
    - Root composer.json requires flarum/tags * -> satisfiable by flarum/tags[1.0.0].
    - flarum/tags 1.0.0 requires flarum/core >=0.1.0-beta.15 <0.1.0-beta.16 -> found flarum/core[v0.1.0-beta.15] but it conflicts with your root composer.json require (^1.1.1).
  Problem 2
    - Root composer.json requires sycho/flarum-profile-cover * -> satisfiable by sycho/flarum-profile-cover[1.0.0].
    - sycho/flarum-profile-cover 1.0.0 requires flarum/core >=0.1.0-beta.15 <=0.1.0-beta.16 -> found flarum/core[v0.1.0-beta.15, v0.1.0-beta.16] but it conflicts with your root composer.json require (^1.1.1).
  Problem 3
    - Root composer.json requires askvortsov/flarum-auto-moderator * -> satisfiable by askvortsov/flarum-auto-moderator[1.0.0].
    - askvortsov/flarum-auto-moderator 1.0.0 requires flarum/core 0.1.0-beta.15 -> found flarum/core[v0.1.0-beta.15] but it conflicts with your root composer.json require (^1.1.1).

<warning>Running update with --no-dev does not mean require-dev is ignored, it just means the packages will not be installed. If dev requirements are blocking the update you have to resolve those problems.</warning>
  ',
            '2.0',
        );

        $majorVersion = $this->lastUpdateCheck->getNewMajorVersion();

        if (! $majorVersion) {
            throw new NoNewMajorVersionException();
        }

        $this->updateComposerJson($majorVersion);

        $this->runCommand($command->dryRun, $majorVersion);

        if ($command->dryRun) {
            $this->composerJson->revert();

            return true;
        }

        $this->events->dispatch(
            new FlarumUpdated($command->actor, FlarumUpdated::MAJOR)
        );

        return true;
    }

    /**
     * @todo change minimum stability to 'stable' and any other similar params
     */
    protected function updateComposerJson(string $majorVersion): void
    {
        $versionNumber = str_replace('v', '', $majorVersion);

        $this->composerJson->require('*', '*');
        $this->composerJson->require('flarum/core', '^'.$versionNumber);
    }

    /**
     * @throws MajorUpdateFailedException
     */
    protected function runCommand(bool $dryRun, string $majorVersion): void
    {
        $input = [
            'command' => 'update',
            '--prefer-dist' => true,
            '--no-plugins' => true,
            '--no-dev' => true,
            '-a' => true,
            '--with-all-dependencies' => true,
        ];

        if ($dryRun) {
            $input['--dry-run'] = true;
        }

        $output = $this->composer->run(new ArrayInput($input));

        if ($output->getExitCode() !== 0) {
            throw new MajorUpdateFailedException('*', $output->getContents(), $majorVersion);
        }
    }
}
