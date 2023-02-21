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
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Illuminate\Support\Arr;

class ListTest extends TestCase
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
                ['id' => 5, 'token' => 'e', 'user_id' => 2, 'last_activity_at' => Carbon::parse('2021-01-01 02:00:00'), 'type' => 'developer'],
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
     * @dataProvider canViewTokensDataProvider
     * @test
     */
    public function user_can_view_access_tokens(int $authenticatedAs, array $canViewIds)
    {
        $response = $this->send(
            $request = $this->request('GET', '/api/access-tokens', compact('authenticatedAs'))
        );

        $data = Arr::get(json_decode($response->getBody()->getContents(), true), 'data');

        $testsTokenId = AccessToken::findValid($request->getAttribute('tests_token'))->id;

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEqualsCanonicalizing(array_merge($canViewIds, [$testsTokenId]), Arr::pluck($data, 'id'));
    }

    /**
     * @dataProvider cannotSeeTokenValuesDataProvider
     * @test
     */
    public function user_cannot_see_token_values(int $authenticatedAs, ?int $userId, array $tokenValues)
    {
        if ($userId) {
            $filters = [
                'filter' => ['user' => $userId]
            ];
        }

        $response = $this->send(
            $this
                ->request('GET', '/api/access-tokens', compact('authenticatedAs'))
                ->withQueryParams($filters ?? [])
        );

        $data = Arr::get(json_decode($response->getBody()->getContents(), true), 'data');

        // There is always an additional null value to refer to the current session.
        if (! $userId || $authenticatedAs === $userId) {
            $tokenValues[] = null;
        }

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEqualsCanonicalizing($tokenValues, Arr::pluck($data, 'attributes.token'));
    }

    /**
     * @dataProvider needsPermissionToUseUserfilterDataProvider
     * @test
     */
    public function user_needs_permissions_to_use_user_filter(int $authenticatedAs, int $userId, array $canViewIds)
    {
        $response = $this->send(
            $request = $this->request('GET', '/api/access-tokens', compact('authenticatedAs'))
                ->withQueryParams([
                    'filter' => ['user' => $userId]
                ])
        );

        $data = Arr::get(json_decode($response->getBody()->getContents(), true), 'data');
        $testsTokenId = AccessToken::findValid($request->getAttribute('tests_token'))->id;

        if ($authenticatedAs === $userId) {
            $canViewIds[] = $testsTokenId;
        }

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEqualsCanonicalizing($canViewIds, Arr::pluck($data, 'id'));
    }

    public function canViewTokensDataProvider(): array
    {
        return [
            // Admin can view his and others access tokens.
            [1, [1, 2, 3, 4, 5, 6]],

            // User with moderateAccessTokens permission can view other users access tokens.
            [4, [1, 2, 3, 4, 5, 6]],

            // Normal users can only view their own.
            [2, [4, 5]],
            [3, [6]],
        ];
    }

    public function cannotSeeTokenValuesDataProvider(): array
    {
        return [
            // Admin can only see his own developer token value.
            [1, null, [null, null, null, null, null, 'c']],
            [1, 1, [null, null, 'c']],
            [1, 2, [null, null]],
            [1, 3, [null]],

            // User with moderateAccessTokens permission can only see his own developer token value.
            [4, null, [null, null, null, null, null, null]],
            [4, 1, [null, null, null]],
            [4, 2, [null, null]],
            [4, 3, [null]],

            // Normal users can only see their own developer token.
            [2, null, ['d', 'e']],
            [3, null, ['f']],
        ];
    }

    public function needsPermissionToUseUserfilterDataProvider(): array
    {
        return [
            // Admin can use user filter.
            [1, 1, [1, 2, 3]],
            [1, 2, [4, 5]],
            [1, 3, [6]],
            [1, 4, []],

            // User with moderateAccessTokens permission can use user filter.
            [4, 1, [1, 2, 3]],
            [4, 2, [4, 5]],
            [4, 3, [6]],
            [4, 4, []],

            // Normal users cannot use the user filter
            [2, 1, []],
            [2, 2, [5, 4]],
            [3, 2, []],
            [3, 3, [6]],
        ];
    }
}
