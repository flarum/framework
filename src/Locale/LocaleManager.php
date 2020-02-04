<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Locale;

use Illuminate\Support\Arr;

class LocaleManager
{
    /**
     * @var Translator
     */
    protected $translator;

    protected $locales = [];

    protected $js = [];

    protected $css = [];

    /**
     * @var string
     */
    protected $cacheDir;

    public function __construct(Translator $translator, string $cacheDir = null)
    {
        $this->translator = $translator;
        $this->cacheDir = $cacheDir;
    }

    public function getLocale(): string
    {
        return $this->translator->getLocale();
    }

    public function setLocale(string $locale)
    {
        $this->translator->setLocale($locale);
    }

    public function addLocale(string $locale, string $name)
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

    public function addTranslations(string $locale, $file, string $module = null)
    {
        $prefix = $module ? $module.'::' : '';

        $this->translator->addResource('prefixed_yaml', compact('file', 'prefix'), $locale);
    }

    public function addJsFile(string $locale, string $js)
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

    public function addCssFile(string $locale, string $css)
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

    public function setTranslator(Translator $translator)
    {
        $this->translator = $translator;
    }

    public function clearCache()
    {
        if ($this->cacheDir) {
            array_map('unlink', glob($this->cacheDir.'/*'));
        }
    }
}
