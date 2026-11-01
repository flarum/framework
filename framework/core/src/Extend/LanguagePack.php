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
use Symfony\Component\Translation\MessageCatalogueInterface;

/**
 * Declaring a language pack's code, title and direction in composer.json as
 * "extra.flarum-locale" is deprecated since 2.1 and will be removed in 3.0:
 * use code(), title() and direction() instead.
 */
class LanguagePack implements ExtenderInterface, LifecycleInterface
{
    private const CORE_LOCALE_FILES = [
        'core',
        'validation',
    ];

    private ?string $code = null;
    private ?string $title = null;
    private ?string $direction = null;

    /**
     * LanguagePack constructor.
     *
     * @param string $path: Path to yaml language files.
     */
    public function __construct(
        private readonly string $path = '/locale'
    ) {
    }

    /**
     * The locale code, such as "en" or "pt-BR".
     */
    public function code(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    /**
     * The language's name, written in that language.
     */
    public function title(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * The direction the language is written in.
     *
     * @param string $direction: "ltr" or "rtl". Defaults to "ltr" when not set.
     */
    public function direction(string $direction): self
    {
        $this->assertValidDirection($direction);

        $this->direction = $direction;

        return $this;
    }

    private function assertValidDirection(mixed $direction): void
    {
        if (! in_array($direction, ['ltr', 'rtl'], true)) {
            throw new InvalidArgumentException(
                'A language pack\'s direction must be "ltr" or "rtl", '.json_encode($direction).' given.'
            );
        }
    }

    public function extend(Container $container, ?Extension $extension = null): void
    {
        if (is_null($extension)) {
            throw new InvalidArgumentException(
                'I need an extension instance to register a language pack'
            );
        }

        $locale = $this->code ?? $extension->composerJsonAttribute('extra.flarum-locale.code');
        $title = $this->title ?? $extension->composerJsonAttribute('extra.flarum-locale.title');
        $direction = $this->direction ?? $extension->composerJsonAttribute('extra.flarum-locale.direction');

        if ($direction !== null) {
            $this->assertValidDirection($direction);
        }

        if (! isset($locale, $title)) {
            throw new RuntimeException(
                'Language packs must set a code and title with LanguagePack::code() and LanguagePack::title(), or define the deprecated "extra.flarum-locale.code" and "extra.flarum-locale.title" in composer.json.'
            );
        }

        $container->resolving(
            LocaleManager::class,
            function (LocaleManager $locales, Container $container) use ($extension, $locale, $title, $direction) {
                $this->registerLocale($container, $locales, $extension, $locale, $title, $direction);
            }
        );
    }

    private function registerLocale(Container $container, LocaleManager $locales, Extension $extension, string $locale, string $title, ?string $direction): void
    {
        $locales->addLocale($locale, $title);
        $locales->setLanguagePackId($locale, $extension->getId());

        if ($direction !== null) {
            $locales->setDirection($locale, $direction);
        }

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

    private function shouldLoad(SplFileInfo $file, Container $container): bool
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

        // Ignore ICU MessageFormat suffixes.
        $slug = str_replace(MessageCatalogueInterface::INTL_DOMAIN_SUFFIX, '', $slug);

        if (in_array($slug, self::CORE_LOCALE_FILES, true)) {
            return true;
        }

        /** @var ExtensionManager|null $extensions */
        static $extensions;
        $extensions ??= $container->make(ExtensionManager::class);

        return $extensions->isEnabled($slug);
    }

    public function onEnable(Container $container, Extension $extension): void
    {
        $container->make('flarum.locales')->clearCache();
    }

    public function onDisable(Container $container, Extension $extension): void
    {
        $container->make('flarum.locales')->clearCache();
    }
}
