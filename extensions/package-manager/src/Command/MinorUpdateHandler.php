<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Command;

use Flarum\ExtensionManager\Composer\ComposerAdapter;
use Flarum\ExtensionManager\Composer\ComposerJson;
use Flarum\ExtensionManager\Event\FlarumUpdated;
use Flarum\ExtensionManager\Exception\ComposerUpdateFailedException;
use Flarum\ExtensionManager\Settings\LastUpdateCheck;
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

    /**
     * @throws \Flarum\User\Exception\PermissionDeniedException
     * @throws ComposerUpdateFailedException
     */
    public function handle(MinorUpdate $command): void
    {
        $command->actor->assertAdmin();

        // Set all extensions to * versioning.
        $this->composerJson->require('*', '*');

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
