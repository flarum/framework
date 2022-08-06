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
                ['id' => 3, 'username' => 'normal3', 'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim', 'email' => 'normal3@machine.local', 'is_email_confirmed' => 1]
            ],
            'access_tokens' => [
                ['id' => 1, 'token' => 'a', 'user_id' => 1, 'last_activity_at' => Carbon::parse('2021-01-01 02:00:00'), 'type' => 'session'],
                ['id' => 2, 'token' => 'b', 'user_id' => 1, 'last_activity_at' => Carbon::parse('2021-01-01 02:00:00'), 'type' => 'session_remember'],
                ['id' => 3, 'token' => 'c', 'user_id' => 1, 'last_activity_at' => Carbon::parse('2021-01-01 02:00:00'), 'type' => 'developer'],
                ['id' => 4, 'token' => 'd', 'user_id' => 2, 'last_activity_at' => Carbon::parse('2021-01-01 02:00:00'), 'type' => 'developer'],
                ['id' => 5, 'token' => 'e', 'user_id' => 2, 'last_activity_at' => Carbon::parse('2021-01-01 02:00:00'), 'type' => 'developer'],
                ['id' => 6, 'token' => 'f', 'user_id' => 3, 'last_activity_at' => Carbon::parse('2021-01-01 02:00:00'), 'type' => 'developer'],
            ],
        ]);
    }

    /**
     * @dataProvider canOnlyViewOwnTokensDataProvider
     * @test
     */
    public function user_can_only_view_own_access_tokens(int $authenticatedAs, array $canViewIds)
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
     * @test
     */
    public function user_cannot_see_session_tokens()
    {
        $response = $this->send(
            $this->request('GET', '/api/access-tokens', [
                'authenticatedAs' => 1,
            ])
        );

        $data = Arr::get(json_decode($response->getBody()->getContents(), true), 'data');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEqualsCanonicalizing([null, null, null, 'c'], Arr::pluck($data, 'attributes.token'));
    }

    public function canOnlyViewOwnTokensDataProvider(): array
    {
        return [
            [1, [1, 2, 3]],
            [2, [4, 5]],
            [3, [6]],
        ];
    }
}
