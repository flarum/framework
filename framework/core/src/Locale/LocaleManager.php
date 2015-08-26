<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Locale;

class LocaleManager
{
    protected $locales = [];

    protected $translations = [];

    protected $js = [];

    protected $config = [];

    public function addLocale($locale, $name)
    {
        $this->locales[$locale] = $name;
    }

    public function getLocales()
    {
        return $this->locales;
    }

    public function hasLocale($locale)
    {
        return isset($this->locales[$locale]);
    }

    public function addTranslations($locale, $translations)
    {
        $this->translations[$locale][] = $translations;
    }

    public function addJsFile($locale, $js)
    {
        $this->js[$locale][] = $js;
    }

    public function addConfig($locale, $config)
    {
        $this->config[$locale][] = $config;
    }

    public function getTranslations($locale)
    {
        $files = array_get($this->translations, $locale, []);

        $parts = explode('-', $locale);

        if (count($parts) > 1) {
            $files = array_merge(array_get($this->translations, $parts[0], []), $files);
        }

        $compiler = new TranslationCompiler($locale, $files);

        return $compiler->getTranslations();
    }

    public function getJsFiles($locale)
    {
        $files = array_get($this->js, $locale, []);

        $parts = explode('-', $locale);

        if (count($parts) > 1) {
            $files = array_merge(array_get($this->js, $parts[0], []), $files);
        }

        return $files;
    }

    public function getConfig($locale)
    {
        if (empty($this->config[$locale])) {
            return [];
        }

        $config = [];

        foreach ($this->config[$locale] as $file) {
            $config = array_merge($config, include $file);
        }

        return $config;
    }
}
