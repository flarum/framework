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
