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
 * Exercises forum-specific Basic authentication, including proxied requests.
 */
class PostmarkWebhookBounceTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setting('mail_driver', 'postmark');
        $this->setting('mail_postmark_token', 'token-abc');
        $this->setting('mail_webhook_username', 'forum-webhooks');
        $this->setting('mail_webhook_password', 'forum-specific-password');

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

    private function postmarkPayload(string $recordType, string $recipient, string $type = 'HardBounce', bool $inactive = true): array
    {
        $date = '2026-07-23T12:00:00Z';

        return [
            'RecordType' => $recordType,
            'MessageID' => 'msg-1',
            'Recipient' => $recipient,
            'Email' => $recipient,
            'Description' => 'The server was unable to deliver your message',
            'Type' => $type,
            'Inactive' => $inactive,
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
    private function postWebhook(array $payload, ?string $ip = null, ?string $credentials = 'forum-webhooks:forum-specific-password')
    {
        $ip ??= PostmarkRequestParser::PROVIDER_IPS[0];

        $body = new Stream('php://temp', 'wb+');
        $body->write(json_encode($payload));
        $body->rewind();

        $request = (new ServerRequest(['REMOTE_ADDR' => $ip], [], '/mail/webhook/postmark', 'POST'))
            ->withHeader('Content-Type', 'application/json')
            ->withBody($body)
            ->withParsedBody($payload);

        if ($credentials !== null) {
            $request = $request->withHeader('Authorization', 'Basic '.base64_encode($credentials));
        }

        return $this->send($request);
    }

    #[Test]
    public function a_proxy_ip_without_credentials_is_rejected(): void
    {
        $response = $this->postWebhook($this->postmarkPayload('Bounce', 'bouncer@example.com'), '10.0.0.1', credentials: null);
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertFalse(User::find(3)->hasBouncedEmail());
    }

    #[Test]
    public function a_postmark_ip_without_credentials_is_rejected(): void
    {
        $response = $this->postWebhook($this->postmarkPayload('Bounce', 'bouncer@example.com'), credentials: null);

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertFalse(User::find(3)->hasBouncedEmail());
        $this->assertEquals(0, EmailBounceEvent::count());
    }

    #[Test]
    public function another_accounts_credentials_are_rejected(): void
    {
        $response = $this->postWebhook($this->postmarkPayload('Bounce', 'bouncer@example.com'), credentials: 'other:password');

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertFalse(User::find(3)->hasBouncedEmail());
        $this->assertEquals(0, EmailBounceEvent::count());
    }

    #[Test]
    public function webhooks_are_refused_until_credentials_are_configured(): void
    {
        $this->setting('mail_webhook_password', '');

        $response = $this->postWebhook($this->postmarkPayload('Bounce', 'bouncer@example.com'));

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertFalse(User::find(3)->hasBouncedEmail());
        $this->assertEquals(0, EmailBounceEvent::count());
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
    public function authenticated_requests_work_behind_a_reverse_proxy(): void
    {
        $response = $this->postWebhook($this->postmarkPayload('Bounce', 'bouncer@example.com'), '10.0.0.1');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue(User::find(3)->hasBouncedEmail());
    }

    #[Test]
    public function a_bounce_that_did_not_deactivate_the_address_does_not_flag(): void
    {
        $response = $this->postWebhook($this->postmarkPayload('Bounce', 'bouncer@example.com', 'SoftBounce', false));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertFalse(User::find(3)->hasBouncedEmail());
        $this->assertEquals(0, EmailBounceEvent::where('email', 'bouncer@example.com')->count());
    }

    #[Test]
    public function a_delivered_event_does_not_flag(): void
    {
        $response = $this->postWebhook($this->postmarkPayload('Delivery', 'bouncer@example.com'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertFalse(User::find(3)->hasBouncedEmail());
    }
}
