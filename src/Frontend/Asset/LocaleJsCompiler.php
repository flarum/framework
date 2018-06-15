<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Frontend\Asset;

class LocaleJsCompiler extends JsCompiler
{
    protected $translations = [];

    public function setTranslations(array $translations)
    {
        $this->translations = $translations;
    }

    public function compile()
    {
        $output = 'flarum.core.app.translator.translations='.json_encode($this->translations).";\n";

        foreach ($this->files as $filename) {
            $output .= file_get_contents($filename);
        }

        return $this->format($output);
    }
}
