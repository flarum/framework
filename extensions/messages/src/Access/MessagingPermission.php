<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Access;

use Flarum\Messages\Dialog;
use Flarum\User\User;

/**
 * Who may send a message where.
 *
 * Members with `dialog.sendMessage` message each other. They can't message
 * someone without it, who couldn't reply, or start a group with them. Holders of
 * `dialog.messageUsersWithoutPermission` can, and once one of them has sent a
 * message in a dialog, everyone in it can reply. Nobody can message someone
 * another extension rules out (UserPolicy::message). Once a group exists,
 * though, its members' own standing doesn't stop the others writing in it.
 */
class MessagingPermission
{
    public const SEND = 'dialog.sendMessage';

    public const MESSAGE_USERS_WITHOUT_PERMISSION = 'dialog.messageUsersWithoutPermission';

    public static function canReply(User $user): bool
    {
        return $user->hasPermission(self::SEND);
    }

    public static function canMessageUsersWithoutPermission(User $user): bool
    {
        return $user->hasPermission(self::MESSAGE_USERS_WITHOUT_PERMISSION);
    }

    /**
     * Whether the actor may send in a dialog they are a member of. Membership
     * itself is the caller's to check: anything serialised to the actor
     * already is one of their dialogs.
     */
    public static function canSendIn(User $actor, Dialog $dialog): bool
    {
        // In a group, a member who can't reply, or can't be written to at
        // all, still leaves others to talk to: they don't hold the group up.
        // The latter just aren't notified (SendMessageNotificationsJob). So
        // only the actor's own right to send counts, which needs no members.
        if ($dialog->isGroup()) {
            return $dialog->anyone_can_reply
                || self::canMessageUsersWithoutPermission($actor)
                || $actor->hasPermission(self::SEND);
        }

        // Never to a member nobody may write to, such as one anonymised by
        // flarum/gdpr, however open the dialog is.
        if ($dialog->users->contains(fn (User $member) => ! $member->is($actor) && $actor->cannot('message', $member))) {
            return false;
        }

        if ($dialog->anyone_can_reply || self::canMessageUsersWithoutPermission($actor)) {
            return true;
        }

        if (! $actor->hasPermission(self::SEND)) {
            return false;
        }

        // Not to a member who can't reply, in a dialog nobody has opened up.
        return $dialog->users->every(
            fn (User $member) => $member->is($actor) || self::canReply($member)
        );
    }
}
