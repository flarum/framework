<?php namespace Flarum\Extend;

use Illuminate\Contracts\Container\Container;

class Locale implements ExtenderInterface
{
    protected $locale;

    protected $translations;

    protected $config;

    protected $js;

    public function __construct($locale)
    {
        $this->locale = $locale;
    }

    public function translations($translations)
    {
        $this->translations = $translations;

        return $this;
    }

    public function config($config)
    {
        $this->config = $config;

        return $this;
    }

    public function js($js)
    {
        $this->js = $js;

        return $this;
    }

    public function extend(Container $container)
    {
        $manager = $container->make('flarum.localeManager');

        if ($this->translations) {
            $manager->addTranslations($this->locale, $this->translations);
        }

        if ($this->config) {
            $manager->addConfig($this->locale, $this->config);
        }

        if ($this->js) {
            $manager->addJsFile($this->locale, $this->js);
        }
    }
}
