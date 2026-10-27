<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Api;

use Flarum\Api\Context;
use Flarum\Api\Resource\UserResource;
use Flarum\Api\Schema;
use Flarum\Messages\Access\MessagingPermission;
use Flarum\Messages\UserDialogState;
use Flarum\User\User;

class UserResourceFields
{
    public function __invoke(): array
    {
        return [
            // Whether a user can be written to, for those who could write to
            // them. Not for guests, or anyone else without the permission: it
            // would tell them who is suspended, and cost a permissions lookup
            // for every user in every response.
            Schema\Boolean::make('canSendAnyMessage')
                ->visible(fn (User $user, Context $context) => $context->getActor()->is($user) || $context->getActor()->can('sendAnyMessage'))
                ->get(fn (User $user, Context $context) => $user->can('sendAnyMessage')),
            // Whether the actor may write to this member at all; another
            // extension can rule them out (flarum/gdpr does for anonymised
            // accounts). Only on user lists and profiles: the recipient picker
            // and the profile's button are what need it.
            Schema\Boolean::make('canMessage')
                ->visible(fn (User $user, Context $context) => $context->collection instanceof UserResource && $context->getActor()->can('sendAnyMessage'))
                ->get(fn (User $user, Context $context) => $context->getActor()->can('message', $user)),
            Schema\Boolean::make('canMessageUsersWithoutPermission')
                ->visible(fn (User $user, Context $context) => $context->getActor()->is($user))
                ->get(fn (User $user) => MessagingPermission::canMessageUsersWithoutPermission($user)),
            // How many of the member's conversations have something unread: the
            // badge on the header's messages icon. Conversations, not messages.
            // One query over the membership table: membership is what
            // visibility means, so there is nothing to add by scoping dialogs
            // separately.
            Schema\Integer::make('unreadDialogCount')
                ->visible(fn (User $user, Context $context) => $context->getActor()->is($user))
                ->get(function (object $model, Context $context) {
                    return UserDialogState::query()
                        ->join('dialogs', 'dialogs.id', '=', 'dialog_user.dialog_id')
                        ->where('dialog_user.user_id', $context->getActor()->id)
                        ->whereColumn('dialog_user.last_read_message_id', '<', 'dialogs.last_message_id')
                        ->count();
                }),
        ];
    }
}
