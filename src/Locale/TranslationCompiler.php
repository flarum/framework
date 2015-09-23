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

        // Temporary solution to resolve references.
        // TODO: Make it do more than one level deep, unit test.
        array_walk_recursive($translations, function (&$value, $key) use ($translations) {
            if (preg_match('/^=>\s*([a-z0-9_\.]+)$/i', $value, $matches)) {
                $value = array_get($translations, $matches[1]);
            }
        });

        return $translations;
    }
}
