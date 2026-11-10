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
        $this->setting('mail_webhook_username', 'forum-webhooks');
        $this->setting('mail_webhook_password', 'forum-specific-password');

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

    #[Test]
    public function an_admin_can_clear_a_bounce(): void
    {
        $this->postWebhook($this->signedPayload('failed', 'bouncer@example.com'));
        $this->assertTrue(User::find(3)->hasBouncedEmail());

        $response = $this->send($this->request('PATCH', '/api/users/3', [
            'authenticatedAs' => 1,
            'json' => ['data' => ['attributes' => ['emailBouncedAt' => null]]],
        ]));

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertFalse(User::find(3)->hasBouncedEmail());
        $this->assertNull(User::find(3)->email_bounce_reason);
    }

    #[Test]
    public function retrying_an_event_does_not_reflag_a_cleared_user(): void
    {
        $payload = $this->signedPayload('failed', 'bouncer@example.com');
        $this->assertEquals(200, $this->postWebhook($payload)->getStatusCode());
        $user = User::find(3);
        $user->clearEmailBounce();
        $user->save();

        $this->assertEquals(200, $this->postWebhook($payload)->getStatusCode());
        $this->assertFalse(User::find(3)->hasBouncedEmail());
        $this->assertEquals(1, \Flarum\Mail\EmailBounceEvent::count());
    }

    #[Test]
    public function reused_event_ids_on_another_day_are_distinct_from_retries(): void
    {
        $payload = $this->signedPayload('failed', 'bouncer@example.com');
        $payload['event-data']['timestamp'] -= 86400;
        $this->assertEquals(200, $this->postWebhook($payload)->getStatusCode());
        $user = User::find(3);
        $user->clearEmailBounce();
        $user->save();

        $payload['event-data']['timestamp'] += 86400;
        $this->assertEquals(200, $this->postWebhook($payload)->getStatusCode());
        $this->assertTrue(User::find(3)->hasBouncedEmail());
        $this->assertEquals(2, \Flarum\Mail\EmailBounceEvent::count());
    }

    #[Test]
    public function deleting_a_user_scrubs_their_bounce_history(): void
    {
        $this->assertEquals(200, $this->postWebhook($this->signedPayload('failed', 'bouncer@example.com'))->getStatusCode());
        User::find(3)->delete();

        $event = \Flarum\Mail\EmailBounceEvent::firstOrFail();
        $this->assertSame('', $event->email);
        $this->assertNull($event->reason);
        $this->assertNull($event->user_id);
    }

    #[Test]
    public function bounce_statistics_are_admin_only(): void
    {
        foreach ([null, 2] as $actor) {
            $response = $this->send($this->request('GET', '/api/mail/bounce-stats', ['authenticatedAs' => $actor]));
            $this->assertEquals(403, $response->getStatusCode());
        }
        $response = $this->send($this->request('GET', '/api/mail/bounce-stats', ['authenticatedAs' => 1]));
        $this->assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function bounce_details_are_private_and_members_cannot_clear_them(): void
    {
        $this->postWebhook($this->signedPayload('failed', 'bouncer@example.com'));
        foreach ([1, 2, 3] as $actor) {
            $response = $this->send($this->request('GET', '/api/users/3', ['authenticatedAs' => $actor]));
            $this->assertEquals(200, $response->getStatusCode());
            $attributes = json_decode((string) $response->getBody(), true)['data']['attributes'];
            $this->assertSame($actor !== 2, array_key_exists('emailBouncedAt', $attributes));
            $this->assertSame($actor === 1, array_key_exists('emailBounceReason', $attributes));
        }
        $response = $this->send($this->request('PATCH', '/api/users/3', [
            'authenticatedAs' => 3,
            'json' => ['data' => ['attributes' => ['emailBouncedAt' => null]]],
        ]));
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertTrue(User::find(3)->hasBouncedEmail());
    }

    #[Test]
    public function a_failed_listener_does_not_consume_the_webhook_receipt(): void
    {
        $attempts = 0;
        $this->app()->getContainer()->make(\Illuminate\Contracts\Events\Dispatcher::class)->listen(
            \Flarum\Mail\Event\EmailBounced::class,
            function () use (&$attempts) {
                if ($attempts++ === 0) {
                    throw new \RuntimeException('Temporary listener failure');
                }
            }
        );
        $payload = $this->signedPayload('failed', 'bouncer@example.com');
        $this->assertEquals(500, $this->postWebhook($payload)->getStatusCode());
        $this->assertEquals(0, \Flarum\Mail\EmailBounceEvent::count());
        $this->assertFalse(User::find(3)->hasBouncedEmail());
        $this->assertEquals(200, $this->postWebhook($payload)->getStatusCode());
        $this->assertEquals(1, \Flarum\Mail\EmailBounceEvent::count());
        $this->assertTrue(User::find(3)->hasBouncedEmail());
    }

    #[Test]
    public function receipts_distinguish_recipients_and_event_types_for_the_same_message(): void
    {
        foreach ([['failed', 'bouncer@example.com'], ['failed', 'normal@machine.local'], ['complained', 'bouncer@example.com']] as [$type, $email]) {
            $this->assertEquals(200, $this->postWebhook($this->signedPayload($type, $email))->getStatusCode());
        }
        $this->assertEquals(3, \Flarum\Mail\EmailBounceEvent::count());
    }

    #[Test]
    public function a_valid_signature_without_forum_credentials_is_rejected(): void
    {
        $payload = $this->signedPayload('failed', 'bouncer@example.com');
        foreach ([null, 'another-forum:password'] as $credentials) {
            $response = $this->postWebhook($payload, $credentials);
            $this->assertEquals(403, $response->getStatusCode());
            $this->assertFalse(User::find(3)->hasBouncedEmail());
            $this->assertEquals(0, \Flarum\Mail\EmailBounceEvent::count());
        }
    }

    #[Test]
    public function a_captured_signature_with_an_altered_recipient_needs_forum_credentials(): void
    {
        $payload = $this->signedPayload('failed', 'bouncer@example.com');
        $payload['event-data']['recipient'] = 'normal@machine.local';
        $payload['event-data']['id'] = 'forged-event';
        $response = $this->postWebhook($payload, credentials: null);
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertFalse(User::find(2)->hasBouncedEmail());
        $this->assertEquals(0, \Flarum\Mail\EmailBounceEvent::count());
    }

    #[Test]
    public function mailgun_is_unconfigured_without_both_forum_credentials(): void
    {
        $this->setting('mail_webhook_password', '');
        $response = $this->postWebhook($this->signedPayload('failed', 'bouncer@example.com'));
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertFalse(User::find(3)->hasBouncedEmail());
    }

    private function signedPayload(string $event, string $recipient): array
    {
        $timestamp = (string) time();
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
                'timestamp' => (float) $timestamp,
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

    private function postWebhook(array $payload, ?string $credentials = 'forum-webhooks:forum-specific-password')
    {
        // Deliberately NOT bypassing CSRF: a real provider webhook cannot send a
        // CSRF token, so the route must be exempt. This also guards that
        // exemption against regressions.
        $request = $this->request('POST', '/mail/webhook/mailgun', ['json' => $payload]);
        if ($credentials !== null) {
            $request = $request->withHeader('Authorization', 'Basic '.base64_encode($credentials));
        }

        return $this->send($request);
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
    public function a_long_provider_reason_is_kept_in_full(): void
    {
        $reason = rtrim(str_repeat('550 5.1.1 The email account that you tried to reach does not exist. ', 6));

        $payload = $this->signedPayload('failed', 'bouncer@example.com');
        $payload['event-data']['delivery-status']['description'] = $reason;

        $response = $this->postWebhook($payload);

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertEquals($reason, User::find(3)->email_bounce_reason);
        $this->assertEquals($reason, \Flarum\Mail\EmailBounceEvent::where('email', 'bouncer@example.com')->value('reason'));
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
    public function a_webhook_is_refused_when_no_signing_key_is_set(): void
    {
        $this->setting('mail_mailgun_webhook_signing_key', '');

        $response = $this->postWebhook($this->signedPayload('failed', 'bouncer@example.com'));

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertFalse(User::find(3)->hasBouncedEmail());
    }

    #[Test]
    public function a_temporary_failure_does_not_flag(): void
    {
        $payload = $this->signedPayload('failed', 'bouncer@example.com');
        $payload['event-data']['severity'] = 'temporary';

        $response = $this->postWebhook($payload);

        $this->assertEquals(200, $response->getStatusCode());
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
