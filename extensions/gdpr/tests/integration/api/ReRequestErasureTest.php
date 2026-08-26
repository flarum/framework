<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Gdpr\tests\integration\api;

use Flarum\Gdpr\Models\ErasureRequest;
use Flarum\Notification\Notification;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

/**
 * A user may request erasure, change their mind and cancel, then later request
 * it again. Each request must send its own confirmation email — otherwise the
 * second request leaves the user waiting for a link that never arrives, with no
 * way to complete the erasure.
 */
class ReRequestErasureTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    public function setUp(): void
    {
        parent::setUp();
        $this->extension('flarum-gdpr');

        $this->setting('mail_driver', 'log');
        $this->setting('forum_title', 'Flarum Test');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
        ]);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        Notification::query()->delete();
        ErasureRequest::query()->delete();
    }

    /**
     * Swap in a transport that records every sent message, so confirmation
     * emails can be counted. Returns the array the captured messages land in.
     *
     * @return \ArrayObject<int, RawMessage>
     */
    private function captureSentMail(): \ArrayObject
    {
        $captured = new \ArrayObject();

        $transport = new class($captured) implements TransportInterface {
            public function __construct(private \ArrayObject $captured)
            {
            }

            public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
            {
                $this->captured[] = $message;

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

        return $captured;
    }

    private function requestErasure(): int
    {
        $response = $this->send(
            $this->request('POST', '/api/user-erasure-requests', [
                'authenticatedAs' => 2,
                'json' => [
                    'data' => ['attributes' => []],
                    'meta' => ['password' => 'too-obscure'],
                ],
            ])->withAttribute('bypassCsrfToken', true)
        );

        $body = (string) $response->getBody();

        $this->assertEquals(201, $response->getStatusCode(), $body);

        return (int) json_decode($body, true)['data']['id'];
    }

    private function cancelErasure(int $id): void
    {
        $response = $this->send(
            $this->request('POST', "/api/user-erasure-requests/$id/cancel", [
                'authenticatedAs' => 2,
            ])->withAttribute('bypassCsrfToken', true)
        );

        $this->assertEquals(204, $response->getStatusCode(), (string) $response->getBody());
    }

    /**
     * Count the confirmation emails among the captured messages, matched by
     * their subject so cancellation emails are not counted. The test locale
     * leaves translation keys untranslated, so the subject is the raw key.
     */
    private function confirmationEmailCount(\ArrayObject $captured): int
    {
        $count = 0;

        foreach ($captured as $message) {
            if ($message instanceof Email && $message->getSubject() === 'flarum-gdpr.email.confirm_erasure.subject') {
                $count++;
            }
        }

        return $count;
    }

    #[Test]
    public function requesting_erasure_sends_a_confirmation_email()
    {
        $captured = $this->captureSentMail();

        $this->requestErasure();

        $this->assertEquals(1, $this->confirmationEmailCount($captured), 'The first request must send a confirmation email.');
    }

    #[Test]
    public function re_requesting_after_cancelling_sends_a_fresh_confirmation_email()
    {
        $captured = $this->captureSentMail();

        $id = $this->requestErasure();
        $this->assertEquals(1, $this->confirmationEmailCount($captured), 'The first request must send a confirmation email.');

        $this->cancelErasure($id);

        $this->requestErasure();

        // The second request is a new decision by the user and must send its
        // own confirmation email; a stale, cancelled request must not swallow
        // it.
        $this->assertEquals(2, $this->confirmationEmailCount($captured), 'Re-requesting after a cancel must send a second confirmation email.');
    }
}
