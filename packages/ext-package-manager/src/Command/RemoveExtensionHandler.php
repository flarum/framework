<?php

namespace SychO\PackageManager\Command;

use Flarum\Extension\ExtensionManager;
use SychO\PackageManager\Extension\PackageManager;

class RemoveExtensionHandler
{
    /**
     * @var ExtensionManager
     */
    protected $extensions;

    /**
     * @var PackageManager
     */
    protected $packages;

    public function __construct(ExtensionManager $extensions, PackageManager $packages)
    {
        $this->extensions = $extensions;
        $this->packages = $packages;
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

        $this->packages->removePackage($extension->name);
    }
}
