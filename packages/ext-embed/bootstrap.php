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
use Flarum\Embed\EmbedFrontend;
use Flarum\Extend;
use Flarum\Extension\Event\Disabled;
use Flarum\Extension\Event\Enabled;
use Flarum\Settings\Event\Saved;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\View\Factory;

return [
    (new Extend\Routes('forum'))
        ->get('/embed/{id:\d+(?:-[^/]*)?}[/{near:[^/]*}]', 'embed.discussion', EmbeddedDiscussionController::class),

    // TODO: Convert to extenders
    function (Dispatcher $events, Factory $view, EmbedFrontend $frontend) {
        $events->listen(Saved::class, function (Saved $event) use ($frontend) {
            if (preg_match('/^theme_|^custom_less$/i', $event->key)) {
                $frontend->getAssets()->flushCss();
            }
        });
        $events->listen(Enabled::class, function () use ($frontend) {
            $frontend->getAssets()->flush();
        });
        $events->listen(Disabled::class, function () use ($frontend) {
            $frontend->getAssets()->flush();
        });

        $view->addNamespace('flarum-embed', __DIR__.'/views');
    }
];
