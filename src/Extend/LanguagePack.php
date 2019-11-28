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
use InvalidArgumentException;
use RuntimeException;

class LanguagePack implements ExtenderInterface, LifecycleInterface
{
    private $path;

    /**
     * LanguagePack constructor.
     *
     * @param string|null $path Path to yaml language files.
     */
    public function __construct(string $path = '/locale')
    {
        $this->path = $path;
    }

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

        $container->resolving(
            LocaleManager::class,
            function (LocaleManager $locales) use ($extension, $locale, $title) {
                $this->registerLocale($locales, $extension, $locale, $title);
            }
        );
    }

    private function registerLocale(LocaleManager $locales, Extension $extension, $locale, $title)
    {
        $locales->addLocale($locale, $title);

        $directory = $extension->getPath().$this->path;

        if (! is_dir($directory)) {
            throw new RuntimeException(
                'Expected to find "'.$this->path.'" directory in language pack.'
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

    public function onEnable(Container $container, Extension $extension)
    {
        $container->make('flarum.locales')->clearCache();
    }

    public function onDisable(Container $container, Extension $extension)
    {
        $container->make('flarum.locales')->clearCache();
    }
}
