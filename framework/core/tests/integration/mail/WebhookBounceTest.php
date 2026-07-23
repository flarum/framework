<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\mail;

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * Drives a real, correctly-signed Mailgun webhook payload through the actual
 * /mail/webhook route and asserts the affected user is flagged. Exercises the
 * whole chain: route -> WebhookController -> Symfony parser (signature check +
 * normalisation) -> EmailBounced/EmailComplained event -> StampBouncedUser.
 */
class WebhookBounceTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    private const SIGNING_KEY = 'test-webhook-signing-key';

    protected function setUp(): void
    {
        parent::setUp();

        $this->setting('mail_driver', 'mailgun');
        $this->setting('mail_mailgun_secret', 'key-abc123');
        $this->setting('mail_mailgun_domain', 'mg.example.com');
        $this->setting('mail_mailgun_region', 'api.mailgun.net');
        $this->setting('mail_mailgun_webhook_signing_key', self::SIGNING_KEY);

        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
                [
                    'id' => 3,
                    'username' => 'bouncer',
                    // BCrypt hash for "too-obscure", as per normalUser().
                    'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim',
                    'email' => 'bouncer@example.com',
                    'is_email_confirmed' => 1,
                ],
            ],
        ]);
    }

    private function signedPayload(string $event, string $recipient): array
    {
        $timestamp = '1700000000';
        $token = 'abc123token';
        $signature = hash_hmac('sha256', $timestamp.$token, self::SIGNING_KEY);

        return [
            'signature' => [
                'timestamp' => $timestamp,
                'token' => $token,
                'signature' => $signature,
            ],
            'event-data' => [
                'event' => $event,
                'id' => 'evt-1',
                'timestamp' => 1700000000.123456,
                'recipient' => $recipient,
                'severity' => 'permanent',
                'delivery-status' => [
                    'code' => 550,
                    'description' => 'No such mailbox',
                    'message' => 'No such mailbox',
                ],
                'reason' => 'bounce',
            ],
        ];
    }

    private function postWebhook(array $payload)
    {
        // Deliberately NOT bypassing CSRF: a real provider webhook cannot send a
        // CSRF token, so the route must be exempt. This also guards that
        // exemption against regressions.
        return $this->send(
            $this->request('POST', '/mail/webhook/mailgun', ['json' => $payload])
        );
    }

    #[Test]
    public function permanent_failure_flags_the_user(): void
    {
        $response = $this->postWebhook($this->signedPayload('failed', 'bouncer@example.com'));

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());

        $user = User::find(3);
        $this->assertNotNull($user->email_bounced_at);
        $this->assertTrue($user->hasBouncedEmail());
    }

    #[Test]
    public function permanent_failure_logs_an_event_row(): void
    {
        $this->postWebhook($this->signedPayload('failed', 'bouncer@example.com'));

        $event = \Flarum\Mail\EmailBounceEvent::where('email', 'bouncer@example.com')->first();

        $this->assertNotNull($event, 'A bounce event row should have been logged.');
        $this->assertEquals(\Flarum\Mail\EmailBounceEvent::TYPE_BOUNCE, $event->type);
        $this->assertEquals(3, $event->user_id);
    }

    #[Test]
    public function complaint_flags_the_user(): void
    {
        $response = $this->postWebhook($this->signedPayload('complained', 'bouncer@example.com'));

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());

        $this->assertTrue(User::find(3)->hasBouncedEmail());
    }

    #[Test]
    public function a_bad_signature_is_rejected_and_does_not_flag(): void
    {
        $payload = $this->signedPayload('failed', 'bouncer@example.com');
        $payload['signature']['signature'] = 'deadbeef';

        $response = $this->postWebhook($payload);

        $this->assertEquals(406, $response->getStatusCode());
        $this->assertFalse(User::find(3)->hasBouncedEmail());
    }

    #[Test]
    public function a_successful_delivery_does_not_flag(): void
    {
        $response = $this->postWebhook($this->signedPayload('delivered', 'bouncer@example.com'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertFalse(User::find(3)->hasBouncedEmail());
    }
}
