<?php

namespace SychO\PackageManager\Command;

use Composer\Console\Application;
use Flarum\Extension\ExtensionManager;
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

    public function __construct(Application $composer, ExtensionManager $extensions,)
    {
        $this->composer = $composer;
        $this->extensions = $extensions;
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
            // ... exception
        }

        $output = new BufferedOutput();
        $input = new ArrayInput([
            'command' => 'remove',
            'packages' => [$extension->name],
        ]);

        $this->composer->run($input, $output);
    }
}
