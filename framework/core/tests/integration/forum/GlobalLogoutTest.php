<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\forum;

use Carbon\Carbon;
use Flarum\Extend;
use Flarum\Http\AccessToken;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\EmailToken;
use Flarum\User\PasswordToken;

class GlobalLogoutTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->extend(
            (new Extend\Csrf)
                ->exemptRoute('globalLogout')
                ->exemptRoute('login')
        );

        $this->prepareDatabase([
            'users' => [
                $this->normalUser()
            ],
            'access_tokens' => [
                ['id' => 1, 'token' => 'a', 'user_id' => 1, 'last_activity_at' => Carbon::parse('2021-01-01 02:00:00'), 'type' => 'session'],
                ['id' => 2, 'token' => 'b', 'user_id' => 1, 'last_activity_at' => Carbon::parse('2021-01-01 02:00:00'), 'type' => 'session_remember'],
                ['id' => 3, 'token' => 'c', 'user_id' => 1, 'last_activity_at' => Carbon::parse('2021-01-01 02:00:00'), 'type' => 'developer'],

                ['id' => 4, 'token' => 'd', 'user_id' => 2, 'last_activity_at' => Carbon::parse('2021-01-01 02:00:00'), 'type' => 'session'],
                ['id' => 5, 'token' => 'e', 'user_id' => 2, 'last_activity_at' => Carbon::parse('2021-01-01 02:00:00'), 'type' => 'developer'],
            ],
            'email_tokens' => [
                ['token' => 'd', 'email' => 'test1@machine.local', 'user_id' => 1, 'created_at' => Carbon::parse('2021-01-01 02:00:00')],
                ['token' => 'e', 'email' => 'test2@machine.local', 'user_id' => 2, 'created_at' => Carbon::parse('2021-01-01 02:00:00')],
            ],
            'password_tokens' => [
                ['token' => 'd', 'user_id' => 1, 'created_at' => Carbon::parse('2021-01-01 02:00:00')],
                ['token' => 'e', 'user_id' => 2, 'created_at' => Carbon::parse('2021-01-01 02:00:00')],
            ]
        ]);
    }

    /**
     * @dataProvider canGloballyLogoutDataProvider
     * @test
     */
    public function can_globally_log_out(int $authenticatedAs, string $identification, string $password)
    {
        $loginResponse = $this->send(
            $this->request('POST', '/login', [
                'json' => compact('identification', 'password')
            ])
        );

        $response = $this->send(
            $this->requestWithCookiesFrom(
                $this->request('POST', '/global-logout'),
                $loginResponse,
            )
        );

        $this->assertEquals(204, $response->getStatusCode());

        $this->assertEquals(0, AccessToken::query()->where('user_id', $authenticatedAs)->count());
        $this->assertEquals(0, EmailToken::query()->where('user_id', $authenticatedAs)->count());
        $this->assertEquals(0, PasswordToken::query()->where('user_id', $authenticatedAs)->count());
    }

    public function canGloballyLogoutDataProvider(): array
    {
        return [
            // Admin
            [1, 'admin', 'password'],

            // Normal user
            [2, 'normal', 'too-obscure'],
        ];
    }
}
