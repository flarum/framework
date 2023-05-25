<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Locale;

use Illuminate\Support\Arr;
use Symfony\Component\Translation\MessageCatalogueInterface;

class LocaleManager
{
    protected array $locales = [];
    protected array $js = [];
    protected array $css = [];

    public function __construct(
        protected Translator $translator,
        protected ?string $cacheDir = null
    ) {
    }

    public function getLocale(): string
    {
        return $this->translator->getLocale();
    }

    public function setLocale(string $locale): void
    {
        $this->translator->setLocale($locale);
    }

    public function addLocale(string $locale, string $name): void
    {
        $this->locales[$locale] = $name;
    }

    public function getLocales(): array
    {
        return $this->locales;
    }

    public function hasLocale(string $locale): bool
    {
        return isset($this->locales[$locale]);
    }

    public function addTranslations(string $locale, $file, string $module = null): void
    {
        $prefix = $module ? $module.'::' : '';

        // `messages` is the default domain, and we want to support MessageFormat
        // for all translations.
        $domain = 'messages'.MessageCatalogueInterface::INTL_DOMAIN_SUFFIX;

        $this->translator->addResource('prefixed_yaml', compact('file', 'prefix'), $locale, $domain);
    }

    public function addJsFile(string $locale, string $js): void
    {
        $this->js[$locale][] = $js;
    }

    public function getJsFiles(string $locale): array
    {
        $files = Arr::get($this->js, $locale, []);

        $parts = explode('-', $locale);

        if (count($parts) > 1) {
            $files = array_merge(Arr::get($this->js, $parts[0], []), $files);
        }

        return $files;
    }

    public function addCssFile(string $locale, string $css): void
    {
        $this->css[$locale][] = $css;
    }

    public function getCssFiles(string $locale): array
    {
        $files = Arr::get($this->css, $locale, []);

        $parts = explode('-', $locale);

        if (count($parts) > 1) {
            $files = array_merge(Arr::get($this->css, $parts[0], []), $files);
        }

        return $files;
    }

    public function getTranslator(): Translator
    {
        return $this->translator;
    }

    public function setTranslator(Translator $translator): void
    {
        $this->translator = $translator;
    }

    public function clearCache(): void
    {
        if ($this->cacheDir) {
            array_map('unlink', glob($this->cacheDir.'/*'));
        }
    }
}
