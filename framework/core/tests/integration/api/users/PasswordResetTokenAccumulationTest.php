<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\users;

use Carbon\Carbon;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\PasswordToken;

class PasswordResetTokenAccumulationTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
                [
                    'id' => 3,
                    'username' => 'normal2',
                    'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim',
                    'email' => 'normal2@machine.local',
                    'is_email_confirmed' => 1,
                ],
            ],
        ]);
    }

    /** @test */
    public function existing_password_tokens_are_deleted_when_new_reset_is_requested()
    {
        $this->app();

        // Simulate two prior reset requests, old enough to be outside the throttle window
        $old = PasswordToken::generate(3);
        $old->created_at = Carbon::now()->subHours(2);
        $old->save();

        $old2 = PasswordToken::generate(3);
        $old2->created_at = Carbon::now()->subHours(1);
        $old2->save();

        $this->assertEquals(2, PasswordToken::query()->where('user_id', 3)->count());

        // Request a new password reset
        $response = $this->send(
            $this->request('POST', '/api/forgot', [
                'authenticatedAs' => 3,
                'json' => ['email' => 'normal2@machine.local'],
            ])
        );

        $this->assertEquals(204, $response->getStatusCode());

        // Should have exactly one token — the new one — not three
        $this->assertEquals(1, PasswordToken::query()->where('user_id', 3)->count());
    }
}
