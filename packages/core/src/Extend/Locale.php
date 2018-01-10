<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Extend;

use DirectoryIterator;
use Flarum\Locale\LocaleManager;
use Illuminate\Contracts\Container\Container;
use RuntimeException;

class Locale implements Extender
{
    protected $directory;

    public function __construct($directory)
    {
        $this->directory = $directory;
    }

    public function __invoke(Container $container)
    {
        $this->loadLanguagePackFrom(
            $this->directory,
            $container->make(LocaleManager::class)
        );
    }

    private function loadLanguagePackFrom($directory, LocaleManager $locales)
    {
        $name = $title = basename($directory);

        if (file_exists($manifest = $directory.'/composer.json')) {
            $json = json_decode(file_get_contents($manifest), true);

            if (empty($json)) {
                throw new RuntimeException("Error parsing composer.json in $name: ".json_last_error_msg());
            }

            $locale = array_get($json, 'extra.flarum-locale.code');
            $title = array_get($json, 'extra.flarum-locale.title', $title);
        }

        if (! isset($locale)) {
            throw new RuntimeException("Language pack $name must define \"extra.flarum-locale.code\" in composer.json.");
        }

        $locales->addLocale($locale, $title);

        if (! is_dir($localeDir = $directory.'/locale')) {
            throw new RuntimeException("Language pack $name must have a \"locale\" subdirectory.");
        }

        if (file_exists($file = $localeDir.'/config.js')) {
            $locales->addJsFile($locale, $file);
        }

        if (file_exists($file = $localeDir.'/config.css')) {
            $locales->addCssFile($locale, $file);
        }

        foreach (new DirectoryIterator($localeDir) as $file) {
            if ($file->isFile() && in_array($file->getExtension(), ['yml', 'yaml'])) {
                $locales->addTranslations($locale, $file->getPathname());
            }
        }
    }
}
