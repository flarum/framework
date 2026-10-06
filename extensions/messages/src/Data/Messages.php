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
    }

    public function delete(): void
    {
        DialogMessage::query()
            ->where('user_id', $this->user->id)
            ->each(fn (DialogMessage $message) => $message->delete());
    }
}
