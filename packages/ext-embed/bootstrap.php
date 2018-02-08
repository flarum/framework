<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Flarum\Embed\EmbeddedDiscussionController;
use Flarum\Embed\Listener;
use Flarum\Extend;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\View\Factory;

return [
    (new Extend\Routes('forum'))
        ->get('/embed/{id:\d+(?:-[^/]*)?}[/{near:[^/]*}]', 'embed.discussion', EmbeddedDiscussionController::class),
    function (Dispatcher $events, Factory $view) {
        $events->subscribe(Listener\FlushEmbedAssetsWhenSettingsAreChanged::class);

        $view->addNamespace('flarum-embed', __DIR__.'/views');
    }
];
