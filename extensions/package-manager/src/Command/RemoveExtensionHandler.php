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
    /**
     * @var ComposerAdapter
     */
    protected $composer;

    /**
     * @var ExtensionManager
     */
    protected $extensions;

    /**
     * @var Dispatcher
     */
    protected $events;

    /**
     * @var ComposerJson
     */
    protected $composerJson;

    public function __construct(ComposerAdapter $composer, ExtensionManager $extensions, Dispatcher $events, ComposerJson $composerJson)
    {
        $this->composer = $composer;
        $this->extensions = $extensions;
        $this->events = $events;
        $this->composerJson = $composerJson;
    }

    /**
     * @throws \Flarum\User\Exception\PermissionDeniedException
     * @throws \Exception
     */
    public function handle(RemoveExtension $command)
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
            $command->task ?? null,
            true
        );

        if ($output->getExitCode() !== 0) {
            throw new ComposerCommandFailedException($extension->name, $output->getContents());
        }

        $this->events->dispatch(
            new Removed($extension)
        );
    }
}
