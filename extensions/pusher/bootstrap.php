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
use Flarum\Pusher\Api\Controller\AuthController;
use Flarum\Pusher\Listener;
use Illuminate\Contracts\Events\Dispatcher;

return [
    (new Extend\Assets('forum'))
        ->asset(__DIR__.'/js/forum/dist/extension.js')
        ->asset(__DIR__.'/less/forum/extension.less')
        ->bootstrapper('flarum/pusher/main'),
    (new Extend\Assets('admin'))
        ->asset(__DIR__.'/js/admin/dist/extension.js')
        ->bootstrapper('flarum/pusher/main'),
    (new Extend\Routes('api'))
        ->post('/pusher/auth', 'pusher.auth', AuthController::class),
    function (Dispatcher $events) {
        $events->subscribe(Listener\AddPusherApi::class);
        $events->subscribe(Listener\PushNewPosts::class);
    },
];
