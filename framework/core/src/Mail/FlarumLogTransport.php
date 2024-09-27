<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mail;

use Illuminate\Mail\Transport\LogTransport;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\RawMessage;

class FlarumLogTransport extends LogTransport
{
    /**
     * {@inheritdoc}
     */
    public function send(RawMessage $message, Envelope $envelope = null): ?SentMessage
    {
        $string = $message->toString();

        if (str_contains($string, 'Content-Transfer-Encoding: quoted-printable')) {
            $string = quoted_printable_decode($string);
        }

        // Overridden to use info, so the log driver works in non-debug mode.
        $this->logger->info($string);

        return new SentMessage($message, $envelope ?? Envelope::create($message));
    }
}
