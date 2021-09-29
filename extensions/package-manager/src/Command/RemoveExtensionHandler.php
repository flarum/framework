<?php

/**
 *
 */

namespace SychO\PackageManager\Command;

use Composer\Console\Application;
use Flarum\Extension\ExtensionManager;
use Illuminate\Contracts\Events\Dispatcher;
use SychO\PackageManager\Exception\ExtensionNotInstalledException;
use SychO\PackageManager\Extension\Event\Removed;
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

    public function __construct(Application $composer, ExtensionManager $extensions, Dispatcher $events)
    {
        $this->composer = $composer;
        $this->extensions = $extensions;
        $this->events = $events;
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

        $this->composer->run($input, $output);

        $this->events->dispatch(
            new Removed($extension)
        );
    }
}
