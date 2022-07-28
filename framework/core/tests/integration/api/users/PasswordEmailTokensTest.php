<?php

namespace Flarum\Tests\integration\api\users;

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\EmailToken;
use Flarum\User\PasswordToken;

class PasswordEmailTokensTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
            ],
        ]);
    }

    /** @test */
    public function actor_has_no_tokens_by_default()
    {
        $this->app();

        $this->assertEquals(0, PasswordToken::query()->where('user_id', 2)->count());
        $this->assertEquals(0, EmailToken::query()->where('user_id', 2)->count());
    }

    /** @test */
    public function password_tokens_are_generated_when_requesting_password_reset()
    {
        $response = $this->send(
            $this->request('POST', '/api/forgot', [
                'authenticatedAs' => 2,
                'json' => [
                    'email' => 'normal@machine.local'
                ]
            ])
        );

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals(1, PasswordToken::query()->where('user_id', 2)->count());
    }

    /** @test */
    public function password_tokens_are_deleted_after_password_reset()
    {
        $this->app();

        // Request password change to generate a token.
        $response = $this->send(
            $this->request('POST', '/api/forgot', [
                'authenticatedAs' => 2,
                'json' => [
                    'email' => 'normal@machine.local'
                ]
            ])
        );

        // Additional Tokens
        PasswordToken::generate(2)->save();
        PasswordToken::generate(2)->save();

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals(3, PasswordToken::query()->where('user_id', 2)->count());

        // Use a token to reset password
        $response = $this->send(
            $request = $this->requestWithCsrfToken(
                $this->request('POST', '/reset', [
                    'authenticatedAs' => 2,
                ])->withParsedBody([
                    'passwordToken' => PasswordToken::query()->latest()->first()->token,
                    'password' => 'new-password',
                    'password_confirmation' => 'new-password',
                ])
            )
        );

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(0, PasswordToken::query()->where('user_id', 2)->count());
    }
}
