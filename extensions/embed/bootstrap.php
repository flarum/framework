<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Flarum\Embed\Listener;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\View\Factory;

return function (Dispatcher $events, Factory $view) {
    $events->subscribe(Listener\AddEmbedRoute::class);
    $events->subscribe(Listener\FlushEmbedAssetsWhenSettingsAreChanged::class);

    $view->addNamespace('flarum-embed', __DIR__.'/views');
};
