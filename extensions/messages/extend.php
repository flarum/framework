<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages;

use Flarum\Api\Endpoint;
use Flarum\Api\Resource;
use Flarum\Audit\Extend\Audit as AuditExtend;
use Flarum\Extend;
use Flarum\Extension\ExtensionManager;
use Flarum\Gdpr\Extend\UserData as GdprUserData;
use Flarum\Messages\Http\Middleware\PopulateDialogWithActor;
use Flarum\Realtime\Extend\Realtime as RealtimeExtend;
use Flarum\Search\Database\DatabaseSearchDriver;
use Flarum\User\User;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less')
        ->jsDirectory(__DIR__.'/js/dist/forum')
        ->route('/messages', 'messages')
        ->route('/messages/dialog/{id:\d+}[/{near:\d+}]', 'messages.dialog'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/less/admin.less'),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\View())->namespace('flarum-messages', __DIR__.'/views'),

    (new Extend\Model(User::class))
        ->belongsToMany('dialogs', Dialog::class, 'dialog_user')
        ->hasMany('dialogMessages', DialogMessage::class, 'user_id'),

    (new Extend\ModelVisibility(Dialog::class))
        ->scope(Access\ScopeDialogVisibility::class),

    (new Extend\ModelVisibility(DialogMessage::class))
        ->scope(Access\ScopeDialogMessageVisibility::class),

    (new Extend\Policy())
        ->modelPolicy(Dialog::class, Access\DialogPolicy::class)
        ->modelPolicy(DialogMessage::class, Access\DialogMessagePolicy::class)
        ->globalPolicy(Access\GlobalPolicy::class),

    new Extend\ApiResource(Api\Resource\DialogResource::class),

    new Extend\ApiResource(Api\Resource\DialogMessageResource::class),

    (new Extend\ApiResource(Resource\UserResource::class))
        ->fields(Api\UserResourceFields::class),

    (new Extend\ThrottleApi())
        ->set('messageTimeout', DialogMessageThrottler::class),

    (new Extend\Middleware('api'))
        ->add(PopulateDialogWithActor::class),

    (new Extend\SearchDriver(DatabaseSearchDriver::class))
        ->addSearcher(Dialog::class, Search\DialogSearcher::class)
        ->addSearcher(DialogMessage::class, Search\DialogMessageSearcher::class)
        ->addFilter(Search\DialogMessageSearcher::class, DialogMessage\Filter\DialogFilter::class)
        ->addFilter(Search\DialogSearcher::class, Dialog\Filter\UnreadFilter::class),

    (new Extend\ServiceProvider())
        ->register(DialogServiceProvider::class),

    (new Extend\Notification())
        ->type(Notification\MessageReceivedBlueprint::class, ['email']),

    (new Extend\Event())
        ->listen(DialogMessage\Event\Created::class, Listener\SendNotificationWhenMessageSent::class)
        ->listen(DialogMessage\Event\Created::class, Listener\UpdateMentionsMetadataWhenVisible::class)
        ->listen(DialogMessage\Event\Updated::class, Listener\UpdateMentionsMetadataWhenVisible::class),

    (new Extend\Conditional())
        // The mention relationships, on by default only where their resources
        // exist. A default include the API can't resolve fails the whole request.
        ->whenExtensionEnabled('flarum-mentions', fn () => [
            (new Extend\ApiResource(Api\Resource\DialogMessageResource::class))
                ->endpoint([Endpoint\Show::class, Endpoint\Index::class], fn (Endpoint\Show|Endpoint\Index $endpoint) => $endpoint
                    ->addDefaultInclude(['mentionsUsers', 'mentionsPosts', 'mentionsGroups'])),
        ])
        ->when(fn (ExtensionManager $extensions) => $extensions->isEnabled('flarum-mentions') && $extensions->isEnabled('flarum-tags'), fn () => [
            (new Extend\ApiResource(Api\Resource\DialogMessageResource::class))
                ->endpoint([Endpoint\Show::class, Endpoint\Index::class], fn (Endpoint\Show|Endpoint\Index $endpoint) => $endpoint
                    ->addDefaultInclude(['mentionsTags'])),
        ])
        // Messages are personal data: exported with the member, addresses removed
        // when they are anonymised, gone when they are erased.
        ->whenExtensionEnabled('flarum-gdpr', fn () => [
            (new GdprUserData())
                ->addType(Data\Messages::class),
        ])
        ->whenExtensionEnabled('flarum-realtime', fn () => [
            (new RealtimeExtend())
                ->broadcastDialogEvent(
                    DialogMessage\Event\Created::class,
                    fn ($event) => $event->message,
                )
                ->registerModelEndpoint(DialogMessage::class, 'dialog-messages')
                ->registerModelEndpoint(Dialog::class, 'dialogs'),
        ])
        ->whenExtensionEnabled('flarum-audit', fn () => [
            (new AuditExtend())
                ->group('flarum-messages')
                ->using(new AuditIntegration()),
        ]),
];
