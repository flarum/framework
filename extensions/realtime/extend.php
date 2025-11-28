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
use Flarum\Extend as Flarum;
use Flarum\Frontend\Document;
use Flarum\Messages\Api\Resource\DialogMessageResource;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;

return [
    (new Flarum\ServiceProvider)
        ->register(WebsocketProvider::class),

    (new Flarum\Console)
        ->command(Websocket\Console\HaltCommand::class)
        ->command(Websocket\Console\ServeCommand::class)
        ->command(Websocket\Console\InfoCommand::class),

    (new Flarum\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->jsDirectory(__DIR__.'/js/dist/forum')
        ->css(__DIR__.'/resources/less/forum.less')
        ->content(function (Document $document) {
            /** @var SettingsRepositoryInterface $settings */
            $settings = resolve(SettingsRepositoryInterface::class);
            $document->payload['flarum-realtime.typing-indicator'] = (bool) $settings->get('flarum-realtime.typing-indicator');
            $document->payload['flarum-realtime.release-discussion-updates'] = (bool) $settings->get('flarum-realtime.release-discussion-updates');
        }),

    (new Flarum\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),

    new Flarum\Locales(__DIR__.'/resources/locale'),

    (new Flarum\Routes('api'))
        ->post('/websocket/auth', 'websocket.auth', Websocket\Api\AuthController::class),

    (new Flarum\ApiResource(Resource\ForumResource::class))
        ->fields(Websocket\Api\ForumAttributes::class),

    (new Flarum\ApiResource(Resource\DiscussionResource::class))
        ->fields(fn () => [
            Schema\Boolean::make('canViewWhoTypes')
                ->get(function (Discussion $model, Context $context) {
                    $settings = resolve(SettingsRepositoryInterface::class);

                    return $settings->get('flarum-realtime.typing-indicator')
                        && $context->getActor()->can('flarum-realtime.view-who-types', $model);
                })
        ]),

    (new Flarum\ApiResource(Resource\UserResource::class))
        ->fields(fn () => [
            Schema\Boolean::make('canViewWhoTypes')
                ->visible(fn (User $user, Context $context) => $context->getActor()->id === $user->id)
                ->get(function (User $model, Context $context) {
                    $settings = resolve(SettingsRepositoryInterface::class);

                    return $settings->get('flarum-realtime.typing-indicator')
                        && $context->getActor()->hasPermissionLike('flarum-realtime.view-who-types');
                })
        ]),

    (new Flarum\Event)
        ->subscribe(Push\Dialog\NewActivity::class)
        ->subscribe(Push\Discussion\NewActivity::class)
        ->subscribe(Push\Post\NewActivity::class),

    (new Flarum\Notification)
        ->driver('realtime', Push\NotificationDriver::class),

    (new Flarum\ApiResource(Resource\PostResource::class))
        ->endpoint('show', fn (Endpoint\Show $endpoint) => $endpoint->addDefaultInclude(['discussion.tags'])),

    (new Flarum\Settings())
        // In seconds. Defaults to 2 minutes.
        ->default('flarum-realtime.release-discussion-updates-interval', 120)
        ->default('flarum-realtime.typing-indicator', true)
        ->default('flarum-realtime.release-discussion-updates', true)
        ->serializeToForum('flarum-realtime.release-discussion-updates-interval', 'flarum-realtime.release-discussion-updates-interval', 'intval'),

    // Disables csrf checks on auth, would time out after being inactive for 60 minutes.
    (new Flarum\Csrf())
        ->exemptRoute('websocket.auth'),

    (new Flarum\User())
        ->registerPreference('flarum-realtime.typing-indicator-full', 'boolVal', true),

    (new Flarum\Conditional())
        ->whenExtensionEnabled('flarum-messages', fn () => [
            // DialogMessage currently doesn't have a read
            (new Flarum\ApiResource(DialogMessageResource::class))
                ->endpoints(fn () => [
                    Endpoint\Show::make()
                        ->authenticated()
                        ->addDefaultInclude(['dialog']),
                ]),
        ]),
];
