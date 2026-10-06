<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages;

use Flarum\Api\Context;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource;
use Flarum\Api\Schema;
use Flarum\Extend;
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

    new Extend\ApiResource(Api\Resource\DialogResource::class),

    new Extend\ApiResource(Api\Resource\DialogMessageResource::class),

    (new Extend\ThrottleApi())
        ->set('messageTimeout', DialogMessageThrottler::class),

    (new Extend\ApiResource(Resource\UserResource::class))
        ->fields(fn () => [
            // Whether a user can be written to, for those who could write to
            // them. Not for guests, or anyone else without the permission: it
            // would tell them who is suspended, and cost a permissions lookup
            // for every user in every response.
            Schema\Boolean::make('canSendAnyMessage')
                ->visible(fn (User $user, Context $context) => $context->getActor()->is($user) || $context->getActor()->can('sendAnyMessage'))
                ->get(fn (User $user, Context $context) => $user->can('sendAnyMessage')),
            // Dialogs with something unread. One query over the membership
            // table: membership is what visibility means, so there is nothing
            // to add by scoping dialogs separately.
            Schema\Integer::make('messageCount')
                ->visible(fn (User $user, Context $context) => $context->getActor()->is($user))
                ->get(function (object $model, Context $context) {
                    return UserDialogState::query()
                        ->join('dialogs', 'dialogs.id', '=', 'dialog_user.dialog_id')
                        ->where('dialog_user.user_id', $context->getActor()->id)
                        ->whereColumn('dialog_user.last_read_message_id', '<', 'dialogs.last_message_id')
                        ->count();
                }),
        ]),

    (new Extend\Middleware('api'))
        ->add(PopulateDialogWithActor::class),

    (new Extend\Policy())
        ->modelPolicy(Dialog::class, Access\DialogPolicy::class)
        ->modelPolicy(DialogMessage::class, Access\DialogMessagePolicy::class)
        ->globalPolicy(Access\GlobalPolicy::class),

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

    // The mention relationships, on by default only where their resources
    // exist. A default include the API can't resolve fails the whole request.
    (new Extend\Conditional())
        ->whenExtensionEnabled('flarum-mentions', fn () => [
            (new Extend\ApiResource(Api\Resource\DialogMessageResource::class))
                ->endpoint([Endpoint\Show::class, Endpoint\Index::class], fn (Endpoint\Show|Endpoint\Index $endpoint) => $endpoint
                    ->addDefaultInclude(['mentionsUsers', 'mentionsPosts', 'mentionsGroups'])),
            (new Extend\Conditional())
                ->whenExtensionEnabled('flarum-tags', fn () => [
                    (new Extend\ApiResource(Api\Resource\DialogMessageResource::class))
                        ->endpoint([Endpoint\Show::class, Endpoint\Index::class], fn (Endpoint\Show|Endpoint\Index $endpoint) => $endpoint
                            ->addDefaultInclude(['mentionsTags'])),
                ]),
        ]),

    // Messages are personal data: exported with the member, addresses removed
    // when they are anonymised, gone when they are erased.
    (new Extend\Conditional())
        ->whenExtensionEnabled('flarum-gdpr', fn () => [
            (new \Flarum\Gdpr\Extend\UserData())
                ->addType(Data\Messages::class),
        ]),

    (new Extend\Conditional())
        ->whenExtensionEnabled('flarum-realtime', fn () => [
            (new RealtimeExtend())
                ->broadcastDialogEvent(
                    DialogMessage\Event\Created::class,
                    fn ($event) => $event->message,
                )
                ->registerModelEndpoint(DialogMessage::class, 'dialog-messages')
                ->registerModelEndpoint(Dialog::class, 'dialogs'),
        ]),
];
