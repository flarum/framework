<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Tests\integration;

use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class MailBounceTest extends TestCase
{
    private const SIGNING_KEY = 'test-webhook-signing-key';

    public function setUp(): void
    {
        parent::setUp();

        $this->setting('mail_driver', 'mailgun');
        $this->setting('mail_mailgun_secret', 'key-abc123');
        $this->setting('mail_mailgun_domain', 'mg.example.com');
        $this->setting('mail_mailgun_region', 'api.mailgun.net');
        $this->setting('mail_mailgun_webhook_signing_key', self::SIGNING_KEY);

        $this->prepareDatabase([
            User::class => [
                [
                    'id' => 3,
                    'username' => 'user3',
                    'email' => 'user3@example.com',
                    'is_email_confirmed' => true,
                    'password' => '$2y$04$3gJO9MUZIyJbOxMbrW2JW.qa8EDxGXSqjYYI7KWZyyg0j6169Udfu', // "secret"
                ],
            ],
        ]);
    }

    private function webhook(string $event, string $recipient): void
    {
        $timestamp = (string) time();
        $token = 'abc123token';

        $this->sendSuccessfulRequest('POST', '/mail/webhook/mailgun', [
            'json' => [
                'signature' => [
                    'timestamp' => $timestamp,
                    'token' => $token,
                    'signature' => hash_hmac('sha256', $timestamp.$token, self::SIGNING_KEY),
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
                    ],
                ],
            ],
        ], 200, null);
    }

    #[Test]
    public function bounce_for_a_known_address_is_logged_against_the_user()
    {
        $this->webhook('failed', 'user3@example.com');

        $this->assertLogExists('user.email_bounced', [
            'matched' => true,
            'user_id' => 3,
            'reason' => 'No such mailbox',
        ], null);
    }

    #[Test]
    public function bounce_for_an_unknown_address_is_logged_with_the_address()
    {
        $this->webhook('failed', 'nobody@example.com');

        $this->assertLogExists('user.email_bounced', [
            'matched' => false,
            'email' => 'nobody@example.com',
            'reason' => 'No such mailbox',
        ], null);
    }

    #[Test]
    public function spam_complaint_for_a_known_address_is_logged_against_the_user()
    {
        $this->webhook('complained', 'user3@example.com');

        $this->assertLogExists('user.email_complained', [
            'matched' => true,
            'user_id' => 3,
        ], null);
    }

    #[Test]
    public function spam_complaint_for_an_unknown_address_is_logged_with_the_address()
    {
        $this->webhook('complained', 'nobody@example.com');

        $this->assertLogExists('user.email_complained', [
            'matched' => false,
            'email' => 'nobody@example.com',
        ], null);
    }
}
