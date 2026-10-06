<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Api;

use Flarum\Api\Context;
use Flarum\Api\Schema;
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
        ];
    }
}
