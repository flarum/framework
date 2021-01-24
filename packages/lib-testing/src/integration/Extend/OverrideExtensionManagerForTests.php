<?php

namespace Flarum\Testing\integration\Extend;

use Flarum\Extend\ExtenderInterface;
use Flarum\Extension\Extension;
use Flarum\Testing\integration\Extension\ExtensionManagerIncludeCurrent;
use Illuminate\Contracts\Container\Container;

class OverrideExtensionManagerForTests implements ExtenderInterface
{
    /**
     * IDs of extensions to boot
     */
    protected $extensions;

    public function __construct($extensions)
    {
        $this->extensions = $extensions;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        if (count($this->extensions)) {
            $container->bind(ExtensionManager::class, ExtensionManagerIncludeCurrent::class);
            $extensionManager = $container->make(ExtensionManager::class);

            foreach ($this->extensions as $extension) {
                $extensionManager->enable($extension);
            }

            $extensionManager->extend($container);
        }
    }
}
