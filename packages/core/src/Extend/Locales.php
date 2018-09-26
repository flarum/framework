<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Extend;

use DirectoryIterator;
use Flarum\Extension\Extension;
use Flarum\Locale\LocaleManager;
use Illuminate\Contracts\Container\Container;

class Locales implements ExtenderInterface
{
    protected $directory;

    public function __construct($directory)
    {
        $this->directory = $directory;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        /** @var LocaleManager $locales */
        $locales = $container->make(LocaleManager::class);

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
}
