<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Flarum\Api\Event\Serializing;
use Flarum\Extend;
use Flarum\Notification\Event\Sending;
use Flarum\Post\Event\Posted;
use Flarum\Pusher\Api\Controller\AuthController;
use Flarum\Pusher\Listener;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),

    (new Extend\Routes('api'))
        ->post('/pusher/auth', 'pusher.auth', AuthController::class),

    function (Dispatcher $events, Container $container) {
        $container->bind(Pusher::class, function ($app) {
            $settings = $app->make(SettingsRepositoryInterface::class);

            $options = [];

            if ($cluster = $settings->get('flarum-pusher.app_cluster')) {
                $options['cluster'] = $cluster;
            }

            return new Pusher(
                $settings->get('flarum-pusher.app_key'),
                $settings->get('flarum-pusher.app_secret'),
                $settings->get('flarum-pusher.app_id'),
                $options
            );
        });

        $events->listen(Posted::class, Listener\PushNewPost::class);
        $events->listen(Sending::class, Listener\PushNotification::class);
        $events->listen(Serializing::class, Listener\AddPusherApi::class);
    },
];
