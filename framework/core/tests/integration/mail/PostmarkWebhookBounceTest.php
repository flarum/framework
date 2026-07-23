<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\mail;

use Flarum\Mail\EmailBounceEvent;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Stream;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Mailer\Bridge\Postmark\Webhook\PostmarkRequestParser;

/**
 * Drives a real Postmark webhook through the actual /mail/webhook route.
 * Postmark has NO signing secret — it is authenticated by verifying the
 * request originates from Postmark's published IP range. This exercises the
 * secret-less, IP-verified path end to end.
 */
class PostmarkWebhookBounceTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setting('mail_driver', 'postmark');
        $this->setting('mail_postmark_token', 'token-abc');

        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
                [
                    'id' => 3,
                    'username' => 'bouncer',
                    'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim',
                    'email' => 'bouncer@example.com',
                    'is_email_confirmed' => 1,
                ],
            ],
        ]);
    }

    private function postmarkPayload(string $recordType, string $recipient): array
    {
        $date = '2026-07-23T12:00:00Z';

        return [
            'RecordType' => $recordType,
            'MessageID' => 'msg-1',
            'Recipient' => $recipient,
            'Email' => $recipient,
            'Description' => 'The server was unable to deliver your message',
            'Type' => 'HardBounce',
            'TypeCode' => 1,
            // The converter reads a record-type-specific timestamp field.
            'BouncedAt' => $date,
            'DeliveredAt' => $date,
            'ReceivedAt' => $date,
            'ChangedAt' => $date,
        ];
    }

    /**
     * Build the request manually so we can set REMOTE_ADDR to a Postmark IP —
     * the request() helper hardcodes empty server params, and Postmark's parser
     * verifies the source IP.
     */
    private function postWebhook(array $payload, ?string $ip = null)
    {
        $ip ??= PostmarkRequestParser::PROVIDER_IPS[0];

        $body = new Stream('php://temp', 'wb+');
        $body->write(json_encode($payload));
        $body->rewind();

        $request = (new ServerRequest(['REMOTE_ADDR' => $ip], [], '/mail/webhook/postmark', 'POST'))
            ->withHeader('Content-Type', 'application/json')
            ->withBody($body)
            ->withParsedBody($payload);

        return $this->send($request);
    }

    #[Test]
    public function bounce_from_a_postmark_ip_flags_the_user(): void
    {
        $response = $this->postWebhook($this->postmarkPayload('Bounce', 'bouncer@example.com'));

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());

        $this->assertTrue(User::find(3)->hasBouncedEmail());
        $this->assertEquals(1, EmailBounceEvent::where('email', 'bouncer@example.com')->count());
    }

    #[Test]
    public function spam_complaint_from_a_postmark_ip_flags_the_user(): void
    {
        $response = $this->postWebhook($this->postmarkPayload('SpamComplaint', 'bouncer@example.com'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue(User::find(3)->hasBouncedEmail());
    }

    #[Test]
    public function request_from_a_non_postmark_ip_is_rejected(): void
    {
        $response = $this->postWebhook($this->postmarkPayload('Bounce', 'bouncer@example.com'), '10.0.0.1');

        // IpsRequestMatcher fails -> RejectWebhookException(406).
        $this->assertEquals(406, $response->getStatusCode());
        $this->assertFalse(User::find(3)->hasBouncedEmail());
    }

    #[Test]
    public function a_delivered_event_does_not_flag(): void
    {
        $response = $this->postWebhook($this->postmarkPayload('Delivery', 'bouncer@example.com'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertFalse(User::find(3)->hasBouncedEmail());
    }
}
