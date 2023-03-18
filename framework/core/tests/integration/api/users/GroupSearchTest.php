<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\users;

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;

class GroupSearchTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    public function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
            ],
        ]);
    }

    /**
     * @test
     */
    public function disallows_group_filter_for_user_without_permission()
    {
        $response = $this->createRequest(['admin']);

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function allows_group_filter_for_admin()
    {
        $response = $this->createRequest(['admin'], 1);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function allows_group_filter_for_user_with_permission()
    {
        $this->prepareDatabase([
            'group_permission' => [
                ['permission' => 'searchUsers', 'group_id' => 2],
            ],
        ]);
        $response = $this->createRequest(['admin'], 2);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function non_admin_gets_correct_results()
    {
        $this->prepareDatabase([
            'group_permission' => [
                ['permission' => 'searchUsers', 'group_id' => 2],
            ],
        ]);

        $response = $this->createRequest(['admin'], 2);
        $responseBodyContents = json_decode($response->getBody()->getContents(), true);

        $this->assertCount(1, $responseBodyContents['data'], json_encode($responseBodyContents));
        $this->assertCount(1, $responseBodyContents['included'], json_encode($responseBodyContents));
        $this->assertEquals(1, $responseBodyContents['included'][0]['id']);

        $response = $this->createRequest(['mod'], 2);
        $responseBodyContents = json_decode($response->getBody()->getContents(), true);

        $this->assertCount(0, $responseBodyContents['data'], json_encode($responseBodyContents));
        $this->assertArrayNotHasKey('included', $responseBodyContents, json_encode($responseBodyContents));

        $response = $this->createRequest(['admins'], 2);
        $responseBodyContents = json_decode($response->getBody()->getContents(), true);

        $this->assertCount(1, $responseBodyContents['data'], json_encode($responseBodyContents));
        $this->assertCount(1, $responseBodyContents['included'], json_encode($responseBodyContents));
        $this->assertEquals(1, $responseBodyContents['included'][0]['id']);

        $response = $this->createRequest(['mods'], 2);
        $responseBodyContents = json_decode($response->getBody()->getContents(), true);

        $this->assertCount(0, $responseBodyContents['data'], json_encode($responseBodyContents));
        $this->assertArrayNotHasKey('included', $responseBodyContents, json_encode($responseBodyContents));

        $response = $this->createRequest(['1'], 2);
        $responseBodyContents = json_decode($response->getBody()->getContents(), true);

        $this->assertCount(1, $responseBodyContents['data'], json_encode($responseBodyContents));
        $this->assertCount(1, $responseBodyContents['included'], json_encode($responseBodyContents));
        $this->assertEquals(1, $responseBodyContents['included'][0]['id']);

        $response = $this->createRequest(['4'], 2);
        $responseBodyContents = json_decode($response->getBody()->getContents(), true);

        $this->assertCount(0, $responseBodyContents['data'], json_encode($responseBodyContents));
        $this->assertArrayNotHasKey('included', $responseBodyContents, json_encode($responseBodyContents));
    }

    /**
     * @test
     */
    public function non_admin_cannot_see_hidden_groups()
    {
        $this->prepareDatabase([
            'group_permission' => [
                ['permission' => 'searchUsers', 'group_id' => 2],
            ],
        ]);

        $this->createHiddenUser();
        $response = $this->createRequest(['99'], 2);
        $responseBodyContents = json_decode($response->getBody()->getContents(), true);

        $this->assertCount(0, $responseBodyContents['data'], json_encode($responseBodyContents));
        $this->assertArrayNotHasKey('included', $responseBodyContents, json_encode($responseBodyContents));
    }

    /**
     * @test
     */
    public function non_admin_can_select_multiple_groups_but_not_hidden()
    {
        $this->prepareDatabase([
            'group_permission' => [
                ['permission' => 'searchUsers', 'group_id' => 2],
            ],
        ]);
        $this->createMultipleUsersAndGroups();
        $response = $this->createRequest(['1', '4', '5', '6', '99'], 2);
        $responseBodyContents = json_decode($response->getBody()->getContents(), true);
        $this->assertCount(4, $responseBodyContents['data'], json_encode($responseBodyContents));
        $this->assertCount(4, $responseBodyContents['included'], json_encode($responseBodyContents));
        $this->assertEquals(1, $responseBodyContents['included'][0]['id']);
        $this->assertEquals(4, $responseBodyContents['included'][1]['id']);
        $this->assertEquals(5, $responseBodyContents['included'][2]['id']);
        $this->assertEquals(6, $responseBodyContents['included'][3]['id']);
    }

    /**
     * @test
     */
    public function admin_gets_correct_results_group()
    {
        $response = $this->createRequest(['admin'], 1);
        $responseBodyContents = json_decode($response->getBody()->getContents(), true);

        $this->assertCount(1, $responseBodyContents['data'], json_encode($responseBodyContents));
        $this->assertCount(1, $responseBodyContents['included'], json_encode($responseBodyContents));
        $this->assertEquals(1, $responseBodyContents['included'][0]['id']);

        $response = $this->createRequest(['mod'], 1);
        $responseBodyContents = json_decode($response->getBody()->getContents(), true);

        $this->assertCount(0, $responseBodyContents['data'], json_encode($responseBodyContents));
        $this->assertArrayNotHasKey('included', $responseBodyContents, json_encode($responseBodyContents));

        $response = $this->createRequest(['admins'], 1);
        $responseBodyContents = json_decode($response->getBody()->getContents(), true);

        $this->assertCount(1, $responseBodyContents['data'], json_encode($responseBodyContents));
        $this->assertCount(1, $responseBodyContents['included'], json_encode($responseBodyContents));
        $this->assertEquals(1, $responseBodyContents['included'][0]['id']);

        $response = $this->createRequest(['mods'], 1);
        $responseBodyContents = json_decode($response->getBody()->getContents(), true);

        $this->assertCount(0, $responseBodyContents['data'], json_encode($responseBodyContents));
        $this->assertArrayNotHasKey('included', $responseBodyContents, json_encode($responseBodyContents));

        $response = $this->createRequest(['1'], 1);
        $responseBodyContents = json_decode($response->getBody()->getContents(), true);

        $this->assertCount(1, $responseBodyContents['data'], json_encode($responseBodyContents));
        $this->assertCount(1, $responseBodyContents['included'], json_encode($responseBodyContents));
        $this->assertEquals(1, $responseBodyContents['included'][0]['id']);

        $response = $this->createRequest(['4'], 1);
        $responseBodyContents = json_decode($response->getBody()->getContents(), true);

        $this->assertCount(0, $responseBodyContents['data'], json_encode($responseBodyContents));
        $this->assertArrayNotHasKey('included', $responseBodyContents, json_encode($responseBodyContents));
    }

    /**
     * @test
     */
    public function admin_can_see_hidden_groups()
    {
        $this->createHiddenUser();
        $response = $this->createRequest(['99'], 1);
        $responseBodyContents = json_decode($response->getBody()->getContents(), true);

        $this->assertCount(1, $responseBodyContents['data'], json_encode($responseBodyContents));
        $this->assertCount(1, $responseBodyContents['included'], json_encode($responseBodyContents));
        $this->assertEquals(99, $responseBodyContents['included'][0]['id']);
    }

    /**
     * @test
     */
    public function admin_can_select_multiple_groups_and_hidden()
    {
        $this->createMultipleUsersAndGroups();
        $this->createHiddenUser();
        $response = $this->createRequest(['1', '4', '5', '6', '99'], 1);
        $responseBodyContents = json_decode($response->getBody()->getContents(), true);
        $this->assertCount(5, $responseBodyContents['data'], json_encode($responseBodyContents));
        $this->assertCount(5, $responseBodyContents['included'], json_encode($responseBodyContents));
        $this->assertEquals(1, $responseBodyContents['included'][0]['id']);
        $this->assertEquals(99, $responseBodyContents['included'][1]['id']);
        $this->assertEquals(4, $responseBodyContents['included'][2]['id']);
        $this->assertEquals(5, $responseBodyContents['included'][3]['id']);
        $this->assertEquals(6, $responseBodyContents['included'][4]['id']);
    }

    private function createRequest(array $group, int $userId = null)
    {
        $auth = $userId ? ['authenticatedAs' => $userId] : [];

        return $this->send(
            $this->request('GET', '/api/users', $auth)
                ->withQueryParams(['filter' => ['q' => 'group:'.implode(',', $group)]])
        );
    }

    private function createMultipleUsersAndGroups()
    {
        $this->prepareDatabase([
            'users' => [
                [
                    'id' => 4,
                    'username' => 'normal4',
                    'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim', // BCrypt hash for "too-obscure"
                    'email' => 'normal4@machine.local',
                    'is_email_confirmed' => 1,
                ],
                [
                    'id' => 5,
                    'username' => 'normal5',
                    'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim', // BCrypt hash for "too-obscure"
                    'email' => 'normal5@machine.local',
                    'is_email_confirmed' => 1,
                ],
                [
                    'id' => 6,
                    'username' => 'normal6',
                    'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim', // BCrypt hash for "too-obscure"
                    'email' => 'normal6@machine.local',
                    'is_email_confirmed' => 1,
                ],
            ],
            'groups' => [
                [
                    'id' => 5,
                    'name_singular' => 'test1 user',
                    'name_plural' => 'test1 users',
                    'is_hidden' => false
                ],
                [
                    'id' => 6,
                    'name_singular' => 'test2 user',
                    'name_plural' => 'test2 users',
                    'is_hidden' => false
                ]
            ],
            'group_user' => [
                [
                    'user_id' => 4,
                    'group_id' => 4
                ],
                [
                    'user_id' => 5,
                    'group_id' => 5
                ],
                [
                    'user_id' => 6,
                    'group_id' => 6
                ],
            ],
        ]);
    }

    private function createHiddenUser()
    {
        $this->prepareDatabase([
            'users' => [
                [
                    'id' => 3,
                    'username' => 'normal2',
                    'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim', // BCrypt hash for "too-obscure"
                    'email' => 'normal2@machine.local',
                    'is_email_confirmed' => 1,
                ],
            ],
            'groups' => [
                [
                    'id' => 99,
                    'name_singular' => 'hidden user',
                    'name_plural' => 'hidden users',
                    'is_hidden' => true
                ],
            ],
            'group_user' => [
                [
                    'user_id' => 3,
                    'group_id' => 99
                ]
            ],
        ]);
    }
}
