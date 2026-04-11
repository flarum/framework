<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\users;

use Carbon\Carbon;
use Flarum\Http\AccessToken;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\PasswordToken;

class PasswordChangeSessionInvalidationTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
            ],
            'access_tokens' => [
                ['id' => 1, 'token' => 'tok_a', 'user_id' => 2, 'last_activity_at' => Carbon::now(), 'type' => 'session'],
                ['id' => 2, 'token' => 'tok_b', 'user_id' => 2, 'last_activity_at' => Carbon::now(), 'type' => 'session_remember'],
                ['id' => 3, 'token' => 'tok_c', 'user_id' => 2, 'last_activity_at' => Carbon::now(), 'type' => 'developer'],
            ],
        ]);
    }

    /** @test */
    public function active_sessions_are_invalidated_when_password_is_changed()
    {
        $this->app();

        $token = PasswordToken::generate(2);
        $token->save();

        // User has existing active sessions before the reset
        $this->assertEquals(3, AccessToken::query()->where('user_id', 2)->count());

        $response = $this->send(
            $this->requestWithCsrfToken(
                $this->request('POST', '/reset')->withParsedBody([
                    'passwordToken' => $token->token,
                    'password' => 'new-password',
                    'password_confirmation' => 'new-password',
                ])
            )
        );

        $this->assertEquals(302, $response->getStatusCode());

        // All prior access tokens should be gone; only the new session token from the reset remains
        $remainingTokens = AccessToken::query()->where('user_id', 2)->get();
        $this->assertEquals(1, $remainingTokens->count());
        $this->assertNotContains('tok_a', $remainingTokens->pluck('token')->all());
        $this->assertNotContains('tok_b', $remainingTokens->pluck('token')->all());
        $this->assertNotContains('tok_c', $remainingTokens->pluck('token')->all());
    }
}
