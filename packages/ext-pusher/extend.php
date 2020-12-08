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
use Flarum\Pusher\PusherNotificationDriver;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Container\Container;

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

    function (Container $container) {
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
    },
];
