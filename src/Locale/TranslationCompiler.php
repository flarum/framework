<?php namespace Flarum\Locale;

use Symfony\Component\Yaml\Yaml;

class TranslationCompiler
{
    protected $locale;

    protected $filenames;

    public function __construct($locale, array $filenames)
    {
        $this->locale = $locale;
        $this->filenames = $filenames;
    }

    public function getTranslations()
    {
        // @todo caching

        $translations = [];

        foreach ($this->filenames as $filename) {
            $translations = array_replace_recursive($translations, Yaml::parse(file_get_contents($filename)));
        }

        return $translations;
    }
}
