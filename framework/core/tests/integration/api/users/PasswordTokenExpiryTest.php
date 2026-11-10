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
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class PasswordTokenExpiryTest extends TestCase
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
    public function valid_password_reset_token_is_accepted(): void
    {
        $this->app();

        $token = PasswordToken::generate(2);
        $token->save();

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
    }

    #[Test]
    public function expired_password_reset_token_is_rejected(): void
    {
        $this->app();

        $token = PasswordToken::generate(2);
        $token->created_at = Carbon::now()->subDays(2);
        $token->save();

        $response = $this->send(
            $this->requestWithCsrfToken(
                $this->request('POST', '/reset')->withParsedBody([
                    'passwordToken' => $token->token,
                    'password' => 'new-password',
                    'password_confirmation' => 'new-password',
                ])
            )
        );

        // Should be rejected — currently findOrFail accepts expired tokens
        $this->assertNotEquals(302, $response->getStatusCode());
    }
}
