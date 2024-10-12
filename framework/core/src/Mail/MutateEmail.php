<?php

namespace Flarum\Mail;

use Illuminate\Mail\Events\MessageSending;

class MutateEmail
{
    public function handle(MessageSending $event): bool
    {
        if (! empty($link = $event->data['unsubscribeLink'])) {
            $headers = $event->message->getHeaders();

            $headers->addTextHeader('List-Unsubscribe', '<'.$link.'>');
        }

        return true;
    }
}
