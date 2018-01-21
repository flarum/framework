<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Flarum\Extend;
use Flarum\Mentions\Listener;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\View\Factory;

return [
    (new Extend\Assets('forum'))
        ->asset(__DIR__.'/js/forum/dist/extension.js')
        ->asset(__DIR__.'/less/forum/extension.less')
        ->bootstrapper('flarum/mentions/main'),
    function (Dispatcher $events, Factory $views) {
        $events->subscribe(Listener\AddPostMentionedByRelationship::class);
        $events->subscribe(Listener\FormatPostMentions::class);
        $events->subscribe(Listener\FormatUserMentions::class);
        $events->subscribe(Listener\UpdatePostMentionsMetadata::class);
        $events->subscribe(Listener\UpdateUserMentionsMetadata::class);
        $events->subscribe(Listener\AddFilterByMentions::class);

        $views->addNamespace('flarum-mentions', __DIR__.'/views');
    },
];
