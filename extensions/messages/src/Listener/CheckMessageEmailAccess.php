<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Listener;

use Flarum\Messages\Dialog;
use Flarum\Messages\DialogMessage;
use Flarum\Messages\Notification\MessageReceivedBlueprint;
use Flarum\User\User;
use Illuminate\Mail\Events\MessageSending;

class CheckMessageEmailAccess
{
    public function handle(MessageSending $event): ?bool
    {
        $blueprint = $event->data['blueprint'] ?? null;
        $recipient = $event->data['user'] ?? null;
        if (! $blueprint instanceof MessageReceivedBlueprint || ! $recipient instanceof User) {
            return null;
        }

        $message = $blueprint->message;
        if (! Dialog::whereVisibleTo($recipient)->whereKey($message->dialog_id)->exists()
            || ($message->user && $message->user->cannot('message', $recipient))
            // A queued email holds the message as it was: deleted since, it isn't sent.
            || DialogMessage::query()->whereKey($message->id)->whereNotNull('hidden_at')->exists()) {
            return false;
        }

        return null;
    }
}
