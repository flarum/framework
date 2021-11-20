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
use Illuminate\Contracts\Events\Dispatcher;
use Flarum\PackageManager\Event\FlarumUpdated;
use Flarum\PackageManager\Exception\ComposerUpdateFailedException;
use Flarum\PackageManager\LastUpdateCheck;
use Symfony\Component\Console\Input\StringInput;

class MinorUpdateHandler
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

    public function __construct(ComposerAdapter $composer, LastUpdateCheck $lastUpdateCheck, Dispatcher $events, ComposerJson $composerJson)
    {
        $this->composer = $composer;
        $this->lastUpdateCheck = $lastUpdateCheck;
        $this->events = $events;
        $this->composerJson = $composerJson;
    }

    /**
     * @throws \Flarum\User\Exception\PermissionDeniedException
     * @throws ComposerUpdateFailedException
     */
    public function handle(MinorUpdate $command)
    {
        $command->actor->assertAdmin();

        $coreRequirement = $this->composerJson->get()['require']['flarum/core'];

        $this->composerJson->require('*', '*');
        $this->composerJson->require('flarum/core', $coreRequirement);

        $output = $this->composer->run(
            new StringInput("update --prefer-dist --no-dev -a --with-all-dependencies")
        );

        if ($output->getExitCode() !== 0) {
            throw new ComposerUpdateFailedException('flarum/*', $output->getContents());
        }

        $this->lastUpdateCheck->forgetAll();

        $this->events->dispatch(
            new FlarumUpdated(FlarumUpdated::MINOR)
        );

        return true;
    }
}
