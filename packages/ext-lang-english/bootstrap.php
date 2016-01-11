<?php

use Illuminate\Contracts\Events\Dispatcher;
use Flarum\Event\ConfigureLocales;

return function (Dispatcher $events) {
    $events->listen(ConfigureLocales::class, function(ConfigureLocales $event) {
        $event->loadLanguagePackFrom(__DIR__);
    });
};
