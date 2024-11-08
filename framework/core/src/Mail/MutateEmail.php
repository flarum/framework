<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mail;

use Illuminate\Mail\Events\MessageSending;

class MutateEmail
{
    public function handle(MessageSending $event): bool
    {
        $unsubscribeLink = $event->data['unsubscribeLink'] ?? null;

        if ($unsubscribeLink) {
            $headers = $event->message->getHeaders();

            $headers->addTextHeader('List-Unsubscribe', '<'.$unsubscribeLink.'>');
        }

        return true;
    }
}
