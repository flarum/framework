<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Command;

use Flarum\Bus\Dispatcher as FlarumDispatcher;
use Flarum\ExtensionManager\Composer\ComposerAdapter;
use Flarum\ExtensionManager\Event\FlarumUpdated;
use Flarum\ExtensionManager\Exception\ComposerUpdateFailedException;
use Flarum\Foundation\Config;
use Illuminate\Contracts\Events\Dispatcher;
use Symfony\Component\Console\Input\ArrayInput;

class GlobalUpdateHandler
{
    public function __construct(
        protected ComposerAdapter $composer,
        protected Dispatcher $events,
        protected FlarumDispatcher $commandDispatcher,
        protected Config $config
    ) {
    }

    /**
     * @throws \Flarum\User\Exception\PermissionDeniedException|ComposerUpdateFailedException
     */
    public function handle(GlobalUpdate $command): void
    {
        $command->actor->assertAdmin();

        $input = [
            'command' => 'update',
            '--prefer-dist' => true,
            '--no-dev' => ! $this->config->inDebugMode(),
            '-a' => true,
            '--with-all-dependencies' => true,
        ];

        $output = $this->composer->run(
            new ArrayInput($input),
            $command->task ?? null,
            true
        );

        if ($output->getExitCode() !== 0) {
            throw new ComposerUpdateFailedException('*', $output->getContents());
        }

        $this->events->dispatch(
            new FlarumUpdated($command->actor, FlarumUpdated::GLOBAL)
        );
    }
}
