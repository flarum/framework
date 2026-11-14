<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\mail;

use Flarum\Extend;
use Flarum\Testing\integration\TestCase;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Contracts\View\Factory;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Mail\Message;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Mime\Email;

/**
 * The mailer dispatches MessageSending with `until()`, which stops at the first
 * listener that returns anything other than null and treats `false` as "don't
 * send". Core's own listener ({@see \Flarum\Mail\MutateEmail}) must therefore
 * return nothing, or every listener registered after it — which is every
 * extension's — is never called.
 */
class MessageSendingListenersTest extends TestCase
{
    private ?Email $sent = null;

    private function sendMail(array $data = []): void
    {
        /** @var Factory $views */
        $views = $this->app()->getContainer()->make(Factory::class);
        $views->addNamespace('flarum-core-test', __DIR__.'/../../fixtures/views');

        /** @var \Illuminate\Contracts\Events\Dispatcher $events */
        $events = $this->app()->getContainer()->make('events');
        $events->listen(MessageSent::class, function (MessageSent $event) {
            $this->sent = $event->message;
        });

        /** @var Mailer $mailer */
        $mailer = $this->app()->getContainer()->make(Mailer::class);

        $mailer->send(
            ['text' => 'flarum-core-test::email.plain-passthrough', 'html' => 'flarum-core-test::email.plain-passthrough'],
            ['bodyText' => 'Hello'] + $data,
            function (Message $message) {
                $message->to('recipient@example.com');
                $message->subject('Test');
            }
        );
    }

    #[Test]
    public function an_extension_listener_can_change_the_outgoing_message()
    {
        $this->extend(
            (new Extend\Event())->listen(MessageSending::class, function (MessageSending $event) {
                $event->message->getHeaders()->addTextHeader('X-Extension', 'was here');
            })
        );

        $this->sendMail();

        $this->assertNotNull($this->sent, 'The mail was never sent.');
        $this->assertSame('was here', $this->sent->getHeaders()->getHeaderBody('X-Extension'), 'The extension\'s MessageSending listener never ran.');
    }

    #[Test]
    public function an_extension_listener_can_stop_a_message_from_being_sent()
    {
        $this->extend(
            (new Extend\Event())->listen(MessageSending::class, fn () => false)
        );

        $this->sendMail();

        $this->assertNull($this->sent, 'A MessageSending listener returning false must stop the mail.');
    }

    #[Test]
    public function the_unsubscribe_header_is_still_added()
    {
        $this->sendMail(['unsubscribeLink' => 'https://example.com/unsubscribe/abc']);

        $this->assertNotNull($this->sent, 'The mail was never sent.');
        $this->assertSame('<https://example.com/unsubscribe/abc>', $this->sent->getHeaders()->getHeaderBody('List-Unsubscribe'));
    }
}
