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
use Flarum\Likes\Listener;
use Illuminate\Contracts\Events\Dispatcher;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),

    function (Dispatcher $events) {
        $events->subscribe(Listener\AddPostLikesRelationship::class);
        $events->subscribe(Listener\SaveLikesToDatabase::class);
        $events->subscribe(Listener\SendNotificationWhenPostIsLiked::class);
    },
];
