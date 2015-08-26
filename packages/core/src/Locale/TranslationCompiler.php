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
