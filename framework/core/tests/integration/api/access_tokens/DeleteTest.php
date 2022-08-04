<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\access_tokens;

use Carbon\Carbon;
use Flarum\Http\AccessToken;
use Flarum\Http\RememberAccessToken;
use Flarum\Http\SessionAccessToken;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Illuminate\Support\Arr;
use Laminas\Diactoros\ServerRequest;

class DeleteTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'normal3', 'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim', 'email' => 'normal3@machine.local', 'is_email_confirmed' => 1]
            ],
            'access_tokens' => [
                ['id' => 1, 'token' => 'a', 'user_id' => 1, 'last_activity_at' => Carbon::parse('2021-01-01 02:00:00'), 'type' => 'session'],
                ['id' => 2, 'token' => 'b', 'user_id' => 1, 'last_activity_at' => Carbon::parse('2021-01-01 02:00:00'), 'type' => 'session_remember'],
                ['id' => 3, 'token' => 'c', 'user_id' => 1, 'last_activity_at' => Carbon::parse('2021-01-01 02:00:00'), 'type' => 'developer'],
                ['id' => 4, 'token' => 'd', 'user_id' => 2, 'last_activity_at' => Carbon::parse('2021-01-01 02:00:00'), 'type' => 'developer'],
                ['id' => 5, 'token' => 'e', 'user_id' => 2, 'last_activity_at' => Carbon::parse('2021-01-01 02:00:00'), 'type' => 'session'],
                ['id' => 6, 'token' => 'f', 'user_id' => 3, 'last_activity_at' => Carbon::parse('2021-01-01 02:00:00'), 'type' => 'developer'],
            ],
        ]);
    }

    /**
     * @dataProvider canDeleteOwnTokensDataProvider
     * @test
     */
    public function user_can_delete_own_tokens(int $authenticatedAs, array $canDeleteIds)
    {
        foreach ($canDeleteIds as $id) {
            $response = $this->send(
                $this->request('DELETE', "/api/access-tokens/$id", compact('authenticatedAs'))
            );

            $this->assertEquals(204, $response->getStatusCode());
        }
    }

    /**
     * @dataProvider cannotDeleteOtherUsersTokensDataProvider
     * @test
     */
    public function user_cannot_delete_other_users_tokens(int $authenticatedAs, array $canDeleteIds)
    {
        foreach ($canDeleteIds as $id) {
            $response = $this->send(
                $this->request('DELETE', "/api/access-tokens/$id", compact('authenticatedAs'))
            );

            $this->assertEquals(404, $response->getStatusCode());
        }
    }

    /**
     * @test
     */
    public function user_cannot_delete_current_session_token()
    {
        $responseWithSession = $this->send(
            $this->requestWithCsrfToken(
                $this->request('POST', '/login', [
                    'json' => [
                        'identification' => 'admin',
                        'password' => 'password',
                    ]
                ])
            )
        );

        $sessionToken = AccessToken::query()
            ->where('user_id', 1)
            ->where('type', SessionAccessToken::$type)
            ->latest()
            ->first();

        $csrfToken = $responseWithSession->getHeaderLine('X-CSRF-Token');

        $request = $this->requestWithCookiesFrom(
            $this->request('DELETE', "/api/access-tokens/$sessionToken->id")->withHeader('X-CSRF-Token', $csrfToken),
            $responseWithSession
        );

        $response = $this->send($request);

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function user_can_terminate_all_other_sessions()
    {
        $response = $this->send(
            $this->request('DELETE', '/api/sessions', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals(
            1,
            AccessToken::query()
                ->where('type', SessionAccessToken::$type)
                ->count()
        );
    }

    public function canDeleteOwnTokensDataProvider(): array
    {
        return [
            [1, [1, 2, 3]],
            [2, [4, 5]],
            [3, [6]],
        ];
    }

    public function cannotDeleteOtherUsersTokensDataProvider(): array
    {
        return [
            [1, [6, 5]],
            [2, [1, 2]],
            [3, [1, 4]],
        ];
    }
}
