<?php namespace Flarum\Locale;

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
        if (! isset($this->translations[$locale])) {
            $this->translations[$locale] = [];
        }

        $this->translations[$locale][] = $translations;
    }

    public function addJsFile($locale, $js)
    {
        if (! isset($this->js[$locale])) {
            $this->js[$locale] = [];
        }

        $this->js[$locale][] = $js;
    }

    public function addConfig($locale, $config)
    {
        if (! isset($this->config[$locale])) {
            $this->config[$locale] = [];
        }

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
}
