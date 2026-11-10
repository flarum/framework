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

/**
 * Covers the LogPasswordResetAttempt middleware, which logs every /api/forgot request — with
 * the originating IP — including attempts for emails that don't match any account. This is the
 * "attempt" event, captured at the HTTP layer so it carries the request IP (unlike the queued
 * worker hook that records the "fulfillment" user.password_change_requested with no IP).
 *
 * The `forgot` route is exempted from CSRF by the base TestCase.
 */
class PasswordResetAttemptTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

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

    #[Test]
    public function attempt_for_existing_email_is_logged_with_match_and_user()
    {
        // Unauthenticated guest request — the real forgot-password flow.
        $this->sendSuccessfulRequest('POST', '/api/forgot', [
            'json' => [
                'email' => 'user3@example.com',
            ],
        ], 204, null);

        // Actor is a guest (null), but the request IP is captured. A known account is identified
        // by user_id only — the raw email is not stored. assertLogExists compares the full payload,
        // so this also asserts the email key is absent.
        $this->assertLogExists('user.password_reset_attempted', [
            'matched' => true,
            'user_id' => 3,
        ], null);

        $log = \Flarum\Audit\AuditLog::query()->where('action', 'user.password_reset_attempted')->first();
        $this->assertArrayNotHasKey('email', $log->payload, 'Email should not be stored for a matched account');
    }

    #[Test]
    public function attempt_for_unknown_email_is_still_logged_as_unmatched()
    {
        $this->sendSuccessfulRequest('POST', '/api/forgot', [
            'json' => [
                'email' => 'nobody@example.com',
            ],
        ], 204, null);

        // No user matches, but the attempt is still recorded (abuse/probe visibility), with no user_id.
        $this->assertLogExists('user.password_reset_attempted', [
            'email' => 'nobody@example.com',
            'matched' => false,
        ], null);
    }
}
