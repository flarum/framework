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
use Flarum\Http\DeveloperAccessToken;
use Flarum\Http\RememberAccessToken;
use Flarum\Http\SessionAccessToken;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;

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
                ['id' => 3, 'username' => 'normal3', 'email' => 'normal3@machine.local', 'is_email_confirmed' => 1],
                ['id' => 4, 'username' => 'normal4', 'email' => 'normal4@machine.local', 'is_email_confirmed' => 1],
            ],
            'access_tokens' => [
                ['id' => 1, 'token' => 'a', 'user_id' => 1, 'last_activity_at' => Carbon::parse('2021-01-01 02:00:00'), 'type' => 'session'],
                ['id' => 2, 'token' => 'b', 'user_id' => 1, 'last_activity_at' => Carbon::parse('2021-01-01 02:00:00'), 'type' => 'session_remember'],
                ['id' => 3, 'token' => 'c', 'user_id' => 1, 'last_activity_at' => Carbon::parse('2021-01-01 02:00:00'), 'type' => 'developer'],
                ['id' => 4, 'token' => 'd', 'user_id' => 2, 'last_activity_at' => Carbon::parse('2021-01-01 02:00:00'), 'type' => 'developer'],
                ['id' => 5, 'token' => 'e', 'user_id' => 2, 'last_activity_at' => Carbon::parse('2021-01-01 02:00:00'), 'type' => 'session'],
                ['id' => 6, 'token' => 'f', 'user_id' => 3, 'last_activity_at' => Carbon::parse('2021-01-01 02:00:00'), 'type' => 'developer'],
            ],
            'groups' => [
                ['id' => 100, 'name_singular' => 'test', 'name_plural' => 'test']
            ],
            'group_user' => [
                ['user_id' => 4, 'group_id' => 100]
            ],
            'group_permission' => [
                ['group_id' => 100, 'permission' => 'moderateAccessTokens']
            ]
        ]);
    }

    /**
     * @dataProvider canDeleteTokensDataProvider
     * @test
     */
    public function user_can_delete_tokens(int $authenticatedAs, array $canDeleteIds)
    {
        foreach ($canDeleteIds as $id) {
            $response = $this->send(
                $this->request('DELETE', "/api/access-tokens/$id", compact('authenticatedAs'))
            );

            $this->assertEquals(204, $response->getStatusCode());
        }
    }

    /**
     * @dataProvider cannotDeleteTokensDataProvider
     * @test
     */
    public function user_cannot_delete_tokens(int $authenticatedAs, array $canDeleteIds)
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
            $this->request('DELETE', '/api/sessions')->withHeader('X-CSRF-Token', $csrfToken),
            $responseWithSession
        );

        $response = $this->send($request);

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals(
            1, // It doesn't delete current session
            AccessToken::query()
                ->where('user_id', 1)
                ->where(function ($query) {
                    $query
                        ->where('type', SessionAccessToken::$type)
                        ->orWhere('type', RememberAccessToken::$type);
                })
                ->count()
        );
    }

    /**
     * @test
     */
    public function terminting_all_other_sessions_does_not_delete_dev_tokens()
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
                ->where('user_id', 1)
                ->where('type', DeveloperAccessToken::$type)
                ->count()
        );
    }

    public function canDeleteTokensDataProvider(): array
    {
        return [
            // Admin can delete any user tokens.
            [1, [1, 2, 3, 4, 5, 6]],

            // User with moderateAccessTokens permission can delete any tokens.
            [4, [1, 2, 3, 4, 5, 6]],

            // Normal users can only delete their own.
            [2, [4, 5]],
            [3, [6]],
        ];
    }

    public function cannotDeleteTokensDataProvider(): array
    {
        return [
            // Normal users cannot delete other users' tokens.
            [2, [1, 2]],
            [3, [1, 4]],
        ];
    }
}
