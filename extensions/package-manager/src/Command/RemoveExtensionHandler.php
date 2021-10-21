<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager\Command;

use Composer\Console\Application;
use Flarum\Extension\ExtensionManager;
use Illuminate\Contracts\Events\Dispatcher;
use Flarum\PackageManager\Exception\ComposerCommandFailedException;
use Flarum\PackageManager\Exception\ComposerUpdateFailedException;
use Flarum\PackageManager\Exception\ExtensionNotInstalledException;
use Flarum\PackageManager\Extension\Event\Removed;
use Flarum\PackageManager\OutputLogger;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class RemoveExtensionHandler
{
    /**
     * @var Application
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
     * @var OutputLogger
     */
    protected $logger;

    public function __construct(Application $composer, ExtensionManager $extensions, Dispatcher $events, OutputLogger $logger)
    {
        $this->composer = $composer;
        $this->extensions = $extensions;
        $this->events = $events;
        $this->logger = $logger;
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

        $output = new BufferedOutput();
        $input = new ArrayInput([
            'command' => 'remove',
            'packages' => [$extension->name],
        ]);

        $exitCode = $this->composer->run($input, $output);
        $output = $output->fetch();

        $this->logger->log($input->__toString(), $output, $exitCode);

        if ($exitCode !== 0) {
            throw new ComposerCommandFailedException($extension->name, $output);
        }

        $this->events->dispatch(
            new Removed($extension)
        );
    }
}
