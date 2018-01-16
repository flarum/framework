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
use Flarum\Pusher\Listener;
use Illuminate\Contracts\Events\Dispatcher;

return [
    (new Extend\Assets('forum'))
        ->defaultAssets(__DIR__)
        ->bootstrapper('flarum/pusher/main'),
    (new Extend\Assets('admin'))
        ->asset(__DIR__.'/js/admin/dist/extension.js')
        ->bootstrapper('flarum/pusher/main'),
    function (Dispatcher $events) {
        $events->subscribe(Listener\AddPusherApi::class);
        $events->subscribe(Listener\PushNewPosts::class);
    },
];
