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
use Flarum\PackageManager\Event\FlarumUpdated;
use Flarum\PackageManager\Exception\ComposerUpdateFailedException;
use Flarum\PackageManager\Settings\LastUpdateCheck;
use Illuminate\Contracts\Events\Dispatcher;
use Symfony\Component\Console\Input\StringInput;

class MinorUpdateHandler
{
    public function __construct(
        protected ComposerAdapter $composer,
        protected LastUpdateCheck $lastUpdateCheck,
        protected Dispatcher $events,
        protected ComposerJson $composerJson
    ) {
    }

    public function handle(MinorUpdate $command): void
    {
        $command->actor->assertAdmin();

        $coreRequirement = $this->composerJson->get()['require']['flarum/core'];

        $this->composerJson->require('*', '*');
        $this->composerJson->require('flarum/core', $coreRequirement);

        $output = $this->composer->run(
            new StringInput('update --prefer-dist --no-dev -a --with-all-dependencies'),
            $command->task ?? null
        );

        if ($output->getExitCode() !== 0) {
            throw new ComposerUpdateFailedException('flarum/*', $output->getContents());
        }

        $this->events->dispatch(
            new FlarumUpdated($command->actor, FlarumUpdated::MINOR)
        );
    }
}
