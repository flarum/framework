<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager\Command;

use Flarum\Extension\ExtensionManager;
use Flarum\PackageManager\Composer\ComposerAdapter;
use Flarum\PackageManager\Exception\ComposerCommandFailedException;
use Flarum\PackageManager\Exception\ExtensionNotInstalledException;
use Flarum\PackageManager\Extension\Event\Removed;
use Illuminate\Contracts\Events\Dispatcher;
use Symfony\Component\Console\Input\StringInput;

class RemoveExtensionHandler
{
    public function __construct(
        private ComposerAdapter $composer,
        private ExtensionManager $extensions,
        private Dispatcher $events
    ) {
    }

    public function handle(RemoveExtension $command): void
    {
        $command->actor->assertAdmin();

        $extension = $this->extensions->getExtension($command->extensionId);

        if (empty($extension)) {
            throw new ExtensionNotInstalledException($command->extensionId);
        }

        if (isset($command->task)) {
            $command->task->package = $extension->name;
        }

        $output = $this->composer->run(
            new StringInput("remove $extension->name"),
            $command->task ?? null
        );

        if ($output->getExitCode() !== 0) {
            throw new ComposerCommandFailedException($extension->name, $output->getContents());
        }

        $this->events->dispatch(
            new Removed($extension)
        );
    }
}
