<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\users;

use Flarum\Http\AccessToken;
use Flarum\Http\DeveloperAccessToken;
use Flarum\Http\RememberAccessToken;
use Flarum\Http\SessionAccessToken;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\PasswordToken;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class PasswordChangeSessionInvalidationTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
        ]);
    }

    #[Test]
    public function active_sessions_are_invalidated_when_password_is_changed(): void
    {
        $this->app();

        // Create existing access tokens of all types for the user
        SessionAccessToken::generate(2);
        RememberAccessToken::generate(2);
        DeveloperAccessToken::generate(2);

        $this->assertEquals(3, AccessToken::query()->where('user_id', 2)->count());

        // Generate a password reset token and use it as an unauthenticated user
        $passwordToken = PasswordToken::generate(2);
        $passwordToken->save();

        $response = $this->send(
            $this->requestWithCsrfToken(
                $this->request('POST', '/reset')->withParsedBody([
                    'passwordToken' => $passwordToken->token,
                    'password' => 'new-password',
                    'password_confirmation' => 'new-password',
                ])
            )
        );

        $this->assertEquals(302, $response->getStatusCode());

        // After password reset, all old access tokens should be gone.
        // Only the new session token created by the reset flow should remain.
        $remainingTokens = AccessToken::query()->where('user_id', 2)->count();

        // Currently fails: old tokens are NOT cleared — only the new session token from the reset should remain
        $this->assertEquals(1, $remainingTokens);
    }
}
