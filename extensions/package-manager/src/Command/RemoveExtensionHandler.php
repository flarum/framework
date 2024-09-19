<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Command;

use Flarum\Extension\ExtensionManager;
use Flarum\ExtensionManager\Composer\ComposerAdapter;
use Flarum\ExtensionManager\Composer\ComposerJson;
use Flarum\ExtensionManager\Exception\ComposerCommandFailedException;
use Flarum\ExtensionManager\Exception\ExtensionNotInstalledException;
use Flarum\ExtensionManager\Exception\IndirectExtensionDependencyCannotBeRemovedException;
use Flarum\ExtensionManager\Extension\Event\Removed;
use Illuminate\Contracts\Events\Dispatcher;
use Symfony\Component\Console\Input\StringInput;

class RemoveExtensionHandler
{
    public function __construct(
        protected ComposerAdapter $composer,
        protected ExtensionManager $extensions,
        protected Dispatcher $events,
        protected ComposerJson $composerJson
    ) {
    }

    /**
     * @throws \Flarum\User\Exception\PermissionDeniedException
     * @throws \Exception
     */
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

        $json = $this->composerJson->get();

        // If this extension is not a direct dependency, we can't actually remove it.
        if (! isset($json['require'][$extension->name]) && ! isset($json['require-dev'][$extension->name])) {
            throw new IndirectExtensionDependencyCannotBeRemovedException($command->extensionId);
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
