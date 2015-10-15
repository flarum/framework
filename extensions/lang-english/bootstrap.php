<?php

use Illuminate\Contracts\Events\Dispatcher;
use Flarum\Event\ConfigureLocales;

return function (Dispatcher $events) {
    $events->listen(ConfigureLocales::class, function(ConfigureLocales $event) {
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

        $event->locales->addLocale($locale, $title);

        if (! is_dir($localeDir = __DIR__.'/locale')) {
            throw new RuntimeException("Language pack $name must have a \"locale\" subdirectory.");
        }

        if (file_exists($file = $localeDir.'/config.js')) {
            $event->locales->addJsFile($locale, $file);
        }

        foreach (new DirectoryIterator($localeDir) as $file) {
            if ($file->isFile() && in_array($file->getExtension(), ['yml', 'yaml'])) {
                $event->locales->addTranslations($locale, $file->getPathname());
            }
        }
    });
};
