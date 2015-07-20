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
        $output = "System.register('locale', [], function() {
    return {
        execute: function() {
            app.translator.translations = ".json_encode($this->translations).";\n";

        foreach ($this->files as $filename) {
            $output .= file_get_contents($filename);
        }

        $output .= "}
    };
});";

        return $output;
    }
}
