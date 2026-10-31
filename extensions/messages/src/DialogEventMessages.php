<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages;

use Flarum\User\User;

/**
 * Notices in a conversation's stream, such as a member leaving it. They are
 * messages, so they take their place in the stream and its numbering, but
 * nothing to read: whoever had read everything still has, nobody is emailed,
 * and they never count as unread.
 */
class DialogEventMessages
{
    /**
     * Writes the notice. The caller announces it (DialogMessage\Event\Created)
     * once its transaction has committed, so nothing queued to act on it can
     * run before it exists.
     */
    public function post(Dialog $dialog, User $user, string $type, array $data = []): DialogMessage
    {
        $previousLast = $dialog->last_message_id;

        $message = new DialogMessage();
        $message->type = $type;
        $message->dialog()->associate($dialog);
        $message->user()->associate($user);
        $message->parsed_content = json_encode((object) $data);
        $message->save();

        // For the number the database gave it.
        $message->refresh();

        $dialog->setLastMessage($message)->save();

        // Members who were up to date stay up to date.
        UserDialogState::query()
            ->where('dialog_id', $dialog->id)
            ->where('last_read_message_id', '>=', (int) $previousLast)
            ->update(['last_read_message_id' => $message->id]);

        return $message;
    }
}
