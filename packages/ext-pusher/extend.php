<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Extend;
use Flarum\Post\Event\Posted;
use Flarum\Pusher\Api\Controller\AuthController;
use Flarum\Pusher\Listener;
use Flarum\Pusher\Provider\PusherProvider;
use Flarum\Pusher\PusherNotificationDriver;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),

    (new Extend\Routes('api'))
        ->post('/pusher/auth', 'pusher.auth', AuthController::class),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\Notification())
        ->driver('pusher', PusherNotificationDriver::class),

    (new Extend\Settings())
        ->serializeToForum('pusherKey', 'flarum-pusher.app_key')
        ->serializeToForum('pusherCluster', 'flarum-pusher.app_cluster'),

    (new Extend\Event())
        ->listen(Posted::class, Listener\PushNewPost::class),

    (new Extend\ServiceProvider())
        ->register(PusherProvider::class),
];
