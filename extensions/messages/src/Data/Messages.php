<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Data;

use Flarum\Gdpr\Data\Type;
use Flarum\Messages\DialogMessage;
use Flarum\Messages\UserDialogState;
use Illuminate\Support\Arr;

/**
 * A member's private messages, for flarum/gdpr. Follows what it does with
 * posts: all of them in the export, addresses removed on anonymising, gone on
 * erasure. Erasure goes through the model so the dialogs' first and last
 * message pointers are repaired as each message leaves.
 */
class Messages extends Type
{
    public static function piiFields(): array
    {
        return ['ip_address'];
    }

    public function export(): ?array
    {
        $export = [];

        DialogMessage::query()
            ->where('user_id', $this->user->id)
            ->orderBy('created_at')
            ->each(function (DialogMessage $message) use (&$export) {
                $export[] = [
                    "messages/message-{$message->id}.json" => $this->encodeForExport(
                        Arr::only($message->toArray(), ['content', 'created_at', 'ip_address', 'dialog_id'])
                    ),
                ];
            });

        return $export ?: null;
    }

    public function anonymize(): void
    {
        DialogMessage::query()
            ->where('user_id', $this->user->id)
            ->update(['ip_address' => null]);

        // Anonymising resets preferences, which would share receipts they had opted out of.
        UserDialogState::query()
            ->where('user_id', $this->user->id)
            ->update(['last_read_message_id' => 0, 'last_read_at' => null]);

        // A NOT NULL column, which GDPR's own user anonymising leaves alone.
        $this->user->newQuery()->whereKey($this->user->id)->update(['marked_all_dialogs_read_id' => 0]);
        $this->user->setAttribute('marked_all_dialogs_read_id', 0)->syncOriginalAttribute('marked_all_dialogs_read_id');
    }

    public function delete(): void
    {
        $messages = DialogMessage::query()->where('user_id', $this->user->id);

        // In one statement up front, rather than one per message below.
        DialogMessage::deleteNotificationsAbout((clone $messages)->select('id')->toBase());

        // One at a time, so each repairs its conversation's pointers.
        DialogMessage::withoutDeletingNotifications(
            fn () => $messages->each(fn (DialogMessage $message) => $message->delete())
        );
    }
}
