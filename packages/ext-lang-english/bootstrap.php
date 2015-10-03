<?php

use Flarum\Events\RegisterLocales;
use Flarum\Core\Application;

return function (Application $app) {
    $app->make('events')->listen(RegisterLocales::class, function(RegisterLocales $event) {
        $name = $title = basename(__DIR__);

        if (file_exists($manifest = __DIR__.'/composer.json')) {
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

        $event->addLocale($locale, $title);

        if (! is_dir($localeDir = __DIR__.'/locale')) {
            throw new RuntimeException("Language pack $name must have a \"locale\" subdirectory.");
        }

        if (file_exists($file = $localeDir.'/config.js')) {
            $event->addJsFile($locale, $file);
        }

        if (file_exists($file = $localeDir.'/config.php')) {
            $event->addConfig($locale, $file);
        }

        foreach (new DirectoryIterator($localeDir) as $file) {
            if ($file->isFile() && in_array($file->getExtension(), ['yml', 'yaml'])) {
                $event->addTranslations($locale, $file->getPathname());
            }
        }
    });
};
