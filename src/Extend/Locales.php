<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extend;

use DirectoryIterator;
use Flarum\Extension\Extension;
use Flarum\Locale\LocaleManager;
use Illuminate\Contracts\Container\Container;

class Locales implements ExtenderInterface, LifecycleInterface
{
    private $directory;

    public function __construct($directory)
    {
        $this->directory = $directory;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $container->resolving(
            LocaleManager::class,
            function (LocaleManager $locales) {
                foreach (new DirectoryIterator($this->directory) as $file) {
                    if (! $file->isFile()) {
                        continue;
                    }

                    $extension = $file->getExtension();
                    if (! in_array($extension, ['yml', 'yaml'])) {
                        continue;
                    }

                    $locales->addTranslations(
                        $file->getBasename(".$extension"),
                        $file->getPathname()
                    );
                }
            }
        );
    }

    public function onEnable(Container $container, Extension $extension)
    {
        $container->make(LocaleManager::class)->clearCache();
    }

    public function onDisable(Container $container, Extension $extension)
    {
        $container->make(LocaleManager::class)->clearCache();
    }
}
