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

        $this->restoreSafeValues($event);

        return true;
    }

    /**
     * Put back any values {@see MailTranslator} held out of the rendered body
     * as markers. Doing it on the finished message — rather than relying on a
     * template to route every translation through {@see MailFormatter} — means
     * the values are restored no matter which template or extension produced
     * the mail, and no template has to change.
     *
     * A body that carries no markers (anything that never went through the mail
     * translator) is left untouched.
     */
    private function restoreSafeValues(MessageSending $event): void
    {
        $message = $event->message;

        $html = $message->getHtmlBody();

        if (is_string($html) && SafeSubstitution::contains($html)) {
            $message->html(SafeSubstitution::restore($html), $message->getHtmlCharset() ?? 'utf-8');
        }

        $text = $message->getTextBody();

        if (is_string($text) && SafeSubstitution::contains($text)) {
            // Plain text has no markup to protect, so the value is restored
            // as-is rather than HTML-escaped.
            $message->text(SafeSubstitution::restore($text, escape: false), $message->getTextCharset() ?? 'utf-8');
        }
    }
}
