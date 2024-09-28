<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages;

use Flarum\Api\Context;
use Flarum\Api\Resource;
use Flarum\Api\Schema;
use Flarum\Extend;
use Flarum\Messages\Http\Middleware\PopulateDialogWithActor;
use Flarum\Search\Database\DatabaseSearchDriver;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less')
        ->jsDirectory(__DIR__.'/js/dist/forum')
        ->route('/messages', 'messages')
        ->route('/messages/dialog/{id:\d+}', 'messages.dialog'),

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

    (new Extend\ApiResource(Resource\UserResource::class))
        ->fields(fn () => [
            Schema\Boolean::make('canSendAnyMessage')
                ->get(fn (object $model, Context $context) => $context->getActor()->can('sendAnyMessage')),
            Schema\Integer::make('messageCount')
                ->get(function (object $model, Context $context) {
                    return Dialog::whereVisibleTo($context->getActor())
                        ->whereHas('users', function (Builder $query) use ($context) {
                            $query->where('dialog_user.user_id', $context->getActor()->id)
                                ->whereColumn('dialog_user.last_read_message_id', '<', 'dialogs.last_message_id');
                        })->count();
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
        ->listen(DialogMessage\Event\Created::class, Listener\SendNotificationWhenMessageSent::class),
];
