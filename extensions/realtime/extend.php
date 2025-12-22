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
use Flarum\Messages\Api\Resource\DialogMessageResource;
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
        ->subscribe(Push\Dialog\NewActivity::class)
        ->subscribe(Push\Discussion\NewActivity::class)
        ->subscribe(Push\Post\NewActivity::class),

    (new Extend\Notification)
        ->driver('realtime', Push\NotificationDriver::class),

    (new Extend\ApiResource(Resource\PostResource::class))
        ->endpoint('show', fn (Endpoint\Show $endpoint) => $endpoint->addDefaultInclude(['discussion.tags'])),

    (new Extend\Settings())
        // In seconds. Defaults to 2 minutes.
        ->default('flarum-realtime.release-discussion-updates-interval', 120)
        ->default('flarum-realtime.typing-indicator', true)
        ->default('flarum-realtime.release-discussion-updates', true)
        ->serializeToForum('flarum-realtime.release-discussion-updates-interval', 'flarum-realtime.release-discussion-updates-interval', 'intval'),

    // Disables csrf checks on auth, would time out after being inactive for 60 minutes.
    (new Extend\Csrf())
        ->exemptRoute('websocket.auth'),

    (new Extend\User())
        ->registerPreference('flarum-realtime.typing-indicator-full', 'boolVal', true),

    (new Extend\Conditional())
        ->whenExtensionEnabled('flarum-messages', fn () => [
            // DialogMessage currently doesn't have a read
            (new Extend\ApiResource(DialogMessageResource::class))
                ->endpoints(fn () => [
                    Endpoint\Show::make()
                        ->authenticated()
                        ->addDefaultInclude(['dialog']),
                ]),
        ]),
];
