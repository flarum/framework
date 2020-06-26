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
use Flarum\Extension\ExtensionManager;
use Flarum\Locale\LocaleManager;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use RuntimeException;
use SplFileInfo;

class LanguagePack implements ExtenderInterface, LifecycleInterface
{
    private const CORE_LOCALE_FILES = [
        'core',
        'validation',
    ];

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
            function (LocaleManager $locales, Container $container) use ($extension, $locale, $title) {
                $this->registerLocale($container, $locales, $extension, $locale, $title);
            }
        );
    }

    private function registerLocale(Container $container, LocaleManager $locales, Extension $extension, $locale, $title)
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
            if ($this->shouldLoad($file, $container)) {
                $locales->addTranslations($locale, $file->getPathname());
            }
        }
    }

    private function shouldLoad(SplFileInfo $file, Container $container)
    {
        if (! $file->isFile()) {
            return false;
        }

        // We are only interested in YAML files
        if (! in_array($file->getExtension(), ['yml', 'yaml'], true)) {
            return false;
        }

        // Some language packs include translations for many extensions
        // from the ecosystems. For performance reasons, we should only
        // load those that belong to core, or extensions that are enabled.
        // To identify them, we compare the filename (without the YAML
        // extension) with the list of known names and all extension IDs.
        $slug = $file->getBasename(".{$file->getExtension()}");

        if (in_array($slug, self::CORE_LOCALE_FILES, true)) {
            return true;
        }

        /** @var ExtensionManager $extensions */
        static $extensions;
        $extensions = $extensions ?? $container->make(ExtensionManager::class);

        return $extensions->isEnabled($slug);
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
