<?php namespace Flarum\Locale;

use Flarum\Assets\RevisionCompiler;

class JsCompiler extends RevisionCompiler
{
    protected $translations = [];

    public function setTranslations(array $translations)
    {
        $this->translations = $translations;
    }

    public function compile()
    {
        $output = "var app = require('flarum/app')['default']; app.translator.translations = ".json_encode($this->translations).";";

        foreach ($this->files as $filename) {
            $output .= file_get_contents($filename);
        }

        return $output;
    }
}
