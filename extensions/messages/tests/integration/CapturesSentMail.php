<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Tests\integration;

use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

/**
 * Keeps the emails the forum sends, so a test can count them.
 */
trait CapturesSentMail
{
    protected \ArrayObject $sent;

    protected function captureSentMail(): void
    {
        $this->sent = $sent = new \ArrayObject();

        $transport = new class($sent) implements TransportInterface {
            public function __construct(private \ArrayObject $sent)
            {
            }

            public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
            {
                $this->sent[] = $message;

                return new SentMessage($message, $envelope ?? Envelope::create($message));
            }

            public function __toString(): string
            {
                return 'capture';
            }
        };

        $container = $this->app()->getContainer();
        $container->instance('symfony.mailer.transport', $transport);
        $container->forgetInstance('mailer');
    }

    protected function emailsTo(string $address): int
    {
        return count(array_filter(
            $this->sent->getArrayCopy(),
            fn ($message) => $message instanceof Email && in_array($address, array_map(fn ($to) => $to->getAddress(), $message->getTo()), true)
        ));
    }
}
