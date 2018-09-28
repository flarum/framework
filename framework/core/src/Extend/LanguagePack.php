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
use InvalidArgumentException;
use RuntimeException;

class LanguagePack implements ExtenderInterface
{
    public function extend(Container $container, Extension $extension = null)
    {
        if (is_null($extension)) {
            throw new InvalidArgumentException(
                'I need an extension instance to register a language pack'
            );
        }

        $locale = $extension->composerJsonAttribute('extra.flarum-locale.code');
        $title = $extension->composerJsonAttribute('extra.flarum-locale.title');

        if (! isset($locale, $title)) {
            throw new RuntimeException(
                'Language packs must define "extra.flarum-locale.code" and "extra.flarum-locale.title" in composer.json.'
            );
        }

        /** @var LocaleManager $locales */
        $locales = $container->make(LocaleManager::class);
        $locales->addLocale($locale, $title);

        $directory = $extension->getPath().'/locale';

        if (! is_dir($directory)) {
            throw new RuntimeException(
                'Language packs must have a "locale" subdirectory.'
            );
        }

        if (file_exists($file = $directory.'/config.js')) {
            $locales->addJsFile($locale, $file);
        }

        if (file_exists($file = $directory.'/config.css')) {
            $locales->addCssFile($locale, $file);
        }

        foreach (new DirectoryIterator($directory) as $file) {
            if ($file->isFile() && in_array($file->getExtension(), ['yml', 'yaml'])) {
                $locales->addTranslations($locale, $file->getPathname());
            }
        }
    }
}
