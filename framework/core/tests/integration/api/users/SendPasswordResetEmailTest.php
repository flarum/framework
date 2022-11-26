<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\users;

use Carbon\Carbon;
use Flarum\Testing\integration\TestCase;
use Flarum\User\Throttler\PasswordResetThrottler;

class SendPasswordResetEmailTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            'users' => [
                [
                    'id' => 3,
                    'username' => 'normal2',
                    'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim', // BCrypt hash for "too-obscure"
                    'email' => 'normal2@machine.local',
                    'is_email_confirmed' => 0,
                    'last_seen_at' => Carbon::now()->subSecond(),
                ],
            ]
        ]);
    }

    /** @test */
    public function users_can_send_password_reset_emails_in_moderate_intervals()
    {
        for ($i = 0; $i < 2; $i++) {
            $response = $this->send(
                $this->request('POST', '/api/forgot', [
                    'authenticatedAs' => 3,
                    'json' => [
                        'email' => 'normal2@machine.local'
                    ]
                ])
            );

            // We don't want to delay tests too long.
            PasswordResetThrottler::$timeout = 5;
            sleep(PasswordResetThrottler::$timeout + 1);
        }

        $this->assertEquals(204, $response->getStatusCode());
    }

    /** @test */
    public function users_cant_send_confirmation_emails_too_fast()
    {
        for ($i = 0; $i < 2; $i++) {
            $response = $this->send(
                $this->request('POST', '/api/forgot', [
                    'authenticatedAs' => 3,
                    'json' => [
                        'email' => 'normal2@machine.local'
                    ]
                ])
            );
        }

        $this->assertEquals(429, $response->getStatusCode());
    }

    /** @test */
    public function request_password_reset_does_not_leak_user_existence()
    {
        $response = $this->send(
            $this->request('POST', '/api/forgot', [
                'authenticatedAs' => 3,
                'json' => [
                    'email' => 'missing_user@machine.local'
                ]
            ])
        );

        $this->assertEquals(204, $response->getStatusCode());
    }
}
