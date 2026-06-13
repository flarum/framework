<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime;

use Flarum\Api\Context;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource;
use Flarum\Api\Schema;
use Flarum\Discussion\Discussion;
use Flarum\Extend;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;

return [
    (new Extend\ServiceProvider)
        ->register(WebsocketProvider::class),

    (new Extend\Console)
        ->command(Websocket\Console\HaltCommand::class)
        ->command(Websocket\Console\ServeCommand::class)
        ->command(Websocket\Console\InfoCommand::class),

    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->jsDirectory(__DIR__.'/js/dist/forum')
        ->css(__DIR__.'/resources/less/forum.less')
        ->content(Content\ForumContent::class),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),

    new Extend\Locales(__DIR__.'/resources/locale'),

    (new Extend\Routes('api'))
        ->post('/websocket/auth', 'websocket.auth', Websocket\Api\AuthController::class),

    (new Extend\ApiResource(Resource\ForumResource::class))
        ->fields(Websocket\Api\ForumAttributes::class),

    (new Extend\ApiResource(Resource\DiscussionResource::class))
        ->fields(fn () => [
            Schema\Boolean::make('canViewWhoTypes')
                ->get(function (Discussion $model, Context $context) {
                    $settings = resolve(SettingsRepositoryInterface::class);

                    return $settings->get('flarum-realtime.typing-indicator')
                        && $context->getActor()->can('flarum-realtime.view-who-types', $model);
                })
        ]),

    (new Extend\ApiResource(Resource\UserResource::class))
        ->fields(fn () => [
            Schema\Boolean::make('canViewWhoTypes')
                ->visible(fn (User $user, Context $context) => $context->getActor()->id === $user->id)
                ->get(function (User $model, Context $context) {
                    $settings = resolve(SettingsRepositoryInterface::class);

                    return $settings->get('flarum-realtime.typing-indicator')
                        && $context->getActor()->hasPermissionLike('flarum-realtime.view-who-types');
                })
        ]),

    (new Extend\Event)
        ->subscribe(Push\EventSubscriber::class)
        ->listen(\Flarum\Notification\Event\Sent::class, Push\Listener\BroadcastNotifications::class)
        ->listen(\Flarum\Settings\Event\Saved::class, Listener\RestartServerOnSettingChange::class),

    (new Extend\Notification)
        ->driver('realtime', Push\NotificationDriver::class),

    (new Extend\ApiResource(Resource\PostResource::class))
        ->endpoint('show', fn (Endpoint\Show $endpoint) => $endpoint->addDefaultInclude(['discussion.tags'])),

    (new Extend\Settings())
        // In seconds. Defaults to 10 seconds.
        ->default('flarum-realtime.release-discussion-updates-interval', 10)
        ->default('flarum-realtime.typing-indicator', true)
        ->default('flarum-realtime.index-typing-indicator', true)
        ->default('flarum-realtime.index-typing-indicator-restricted', false)
        ->default('flarum-realtime.release-discussion-updates', true)
        ->default('flarum-realtime.notification-toast-dismiss-after', 10)
        ->serializeToForum('flarum-realtime.release-discussion-updates-interval', 'flarum-realtime.release-discussion-updates-interval', 'intval')
        ->serializeToForum('flarum-realtime.notification-toast-dismiss-after', 'flarum-realtime.notification-toast-dismiss-after', 'intval'),

    // Disables csrf checks on auth, would time out after being inactive for 60 minutes.
    (new Extend\Csrf())
        ->exemptRoute('websocket.auth'),

    (new Extend\User())
        ->registerPreference('flarum-realtime.typing-indicator-full', 'boolVal', true),

];
