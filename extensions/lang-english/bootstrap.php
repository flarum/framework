<?php

use Flarum\Events\RegisterLocales;
use Illuminate\Events\Dispatcher;

return function (Dispatcher $events) {
    $events->listen(RegisterLocales::class, function(RegisterLocales $event) {
        $locale = $name = null;

        if (file_exists($manifest = __DIR__.'/flarum.json')) {
            $json = json_decode(file_get_contents($manifest), true);
            $locale = array_key_exists('locale', $json) ? $json['locale'] : null;
            $name = array_key_exists('name', $json) ? $json['name'] : null;
            unset($json);
        }

        if ($name === null) {
            throw new RuntimeException("Language pack ".__DIR__." needs a \"name\" in flarum.json.");
        }

        if ($locale === null) {
            throw new RuntimeException("Language pack {$name} needs a \"locale\" in flarum.json.");
        }

        $event->addLocale($locale, $name);

        if (! is_dir($localeDir = __DIR__.'/locale')) {
            throw new RuntimeException("Language pack {$name} needs a \"locale\" subdirectory.");
        }

        if (file_exists($file = $localeDir.'/config.js')) {
            $event->addJsFile($locale, $file);
        }

        if (file_exists($file = $localeDir.'/config.php')) {
            $event->addConfig($locale, $file);
        }

        $files = new DirectoryIterator($localeDir);

        foreach ($files as $file) {
            if ($file->isFile() && in_array($file->getExtension(), ['yml', 'yaml'])) {
                $event->addTranslations($locale, $file->getPathname());
            }
        }
    });
};
