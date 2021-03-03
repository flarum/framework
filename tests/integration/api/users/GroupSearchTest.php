<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\users;

use Flarum\Tests\integration\RetrievesAuthorizedUsers;
use Flarum\Tests\integration\TestCase;

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
        $response = $this->send(
            $this->request('GET', '/api/users')
                ->withQueryParams(['filter' => ['q' => 'group:admin']])
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function allows_group_filter_for_admin()
    {
        $response = $this->send(
            $this->request('GET', '/api/users', [
                'authenticatedAs' => 1,
            ])->withQueryParams(['filter' => ['q' => 'group:admin']])
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function allows_group_filter_for_user_with_permission()
    {
        $this->prepareDatabase([
            'group_permission' => [
                ['permission' => 'viewUserList', 'group_id' => 2],
            ],
        ]);
        $response = $this->send(
            $this->request('GET', '/api/users')
                ->withQueryParams(['filter' => ['q' => 'group:admin']])
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function non_admin_gets_correct_results()
    {
        $this->prepareDatabase([
            'group_permission' => [
                ['permission' => 'viewUserList', 'group_id' => 2],
            ],
        ]);

        $response = $this->send(
            $this->request('GET', '/api/users')
                ->withQueryParams(['filter' => ['q' => 'group:admin']])
        );
        $responseBodyContents = json_decode($response->getBody()->getContents());

        $this->assertCount(1, $responseBodyContents->data, json_encode($responseBodyContents));
        $this->assertCount(1, $responseBodyContents->included, json_encode($responseBodyContents));
        $this->assertEquals(1, $responseBodyContents->included[0]->id);

        $response = $this->send(
            $this->request('GET', '/api/users')
                ->withQueryParams(['filter' => ['q' => 'group:mod']])
        );
        $responseBodyContents = json_decode($response->getBody()->getContents());

        $this->assertCount(0, $responseBodyContents->data, json_encode($responseBodyContents));
        $this->assertObjectNotHasAttribute('included', $responseBodyContents, json_encode($responseBodyContents));

        $response = $this->send(
            $this->request('GET', '/api/users')
                ->withQueryParams(['filter' => ['q' => 'group:admins']])
        );
        $responseBodyContents = json_decode($response->getBody()->getContents());

        $this->assertCount(1, $responseBodyContents->data, json_encode($responseBodyContents));
        $this->assertCount(1, $responseBodyContents->included, json_encode($responseBodyContents));
        $this->assertEquals(1, $responseBodyContents->included[0]->id);

        $response = $this->send(
            $this->request('GET', '/api/users')
                ->withQueryParams(['filter' => ['q' => 'group:mods']])
        );
        $responseBodyContents = json_decode($response->getBody()->getContents());

        $this->assertCount(0, $responseBodyContents->data, json_encode($responseBodyContents));
        $this->assertObjectNotHasAttribute('included', $responseBodyContents, json_encode($responseBodyContents));

        $response = $this->send(
            $this->request('GET', '/api/users')
                ->withQueryParams(['filter' => ['q' => 'group:1']])
        );
        $responseBodyContents = json_decode($response->getBody()->getContents());

        $this->assertCount(1, $responseBodyContents->data, json_encode($responseBodyContents));
        $this->assertCount(1, $responseBodyContents->included, json_encode($responseBodyContents));
        $this->assertEquals(1, $responseBodyContents->included[0]->id);

        $response = $this->send(
            $this->request('GET', '/api/users')
                ->withQueryParams(['filter' => ['q' => 'group:4']])
        );
        $responseBodyContents = json_decode($response->getBody()->getContents());

        $this->assertCount(0, $responseBodyContents->data, json_encode($responseBodyContents));
        $this->assertObjectNotHasAttribute('included', $responseBodyContents, json_encode($responseBodyContents));
    }

    /**
     * @test
     */
    public function non_admin_cannot_see_hidden_groups()
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
        $response = $this->send(
            $this->request('GET', '/api/users', [
                'authenticatedAs' => 2
            ])->withQueryParams(['filter' => ['q' => 'group:99']])
        );
        $responseBodyContents = json_decode($response->getBody()->getContents());

        $this->assertCount(0, $responseBodyContents->data, json_encode($responseBodyContents));
        $this->assertObjectNotHasAttribute('included', $responseBodyContents, json_encode($responseBodyContents));
    }

    /**
     * @test
     */
    public function admin_gets_correct_results_group()
    {
        $response = $this->send(
            $this->request('GET', '/api/users', [
                'authenticatedAs' => 1
            ])->withQueryParams(['filter' => ['q' => 'group:admin']])
        );
        $responseBodyContents = json_decode($response->getBody()->getContents());

        $this->assertCount(1, $responseBodyContents->data, json_encode($responseBodyContents));
        $this->assertCount(1, $responseBodyContents->included, json_encode($responseBodyContents));
        $this->assertEquals(1, $responseBodyContents->included[0]->id);

        $response = $this->send(
            $this->request('GET', '/api/users', [
                'authenticatedAs' => 1
            ])->withQueryParams(['filter' => ['q' => 'group:mod']])
        );
        $responseBodyContents = json_decode($response->getBody()->getContents());

        $this->assertCount(0, $responseBodyContents->data, json_encode($responseBodyContents));
        $this->assertObjectNotHasAttribute('included', $responseBodyContents, json_encode($responseBodyContents));

        $response = $this->send(
            $this->request('GET', '/api/users', [
                'authenticatedAs' => 1
            ])->withQueryParams(['filter' => ['q' => 'group:admins']])
        );
        $responseBodyContents = json_decode($response->getBody()->getContents());

        $this->assertCount(1, $responseBodyContents->data, json_encode($responseBodyContents));
        $this->assertCount(1, $responseBodyContents->included, json_encode($responseBodyContents));
        $this->assertEquals(1, $responseBodyContents->included[0]->id);

        $response = $this->send(
            $this->request('GET', '/api/users', [
                'authenticatedAs' => 1
            ])->withQueryParams(['filter' => ['q' => 'group:mods']])
        );
        $responseBodyContents = json_decode($response->getBody()->getContents());

        $this->assertCount(0, $responseBodyContents->data, json_encode($responseBodyContents));
        $this->assertObjectNotHasAttribute('included', $responseBodyContents, json_encode($responseBodyContents));

        $response = $this->send(
            $this->request('GET', '/api/users', [
                'authenticatedAs' => 1
            ])->withQueryParams(['filter' => ['q' => 'group:1']])
        );
        $responseBodyContents = json_decode($response->getBody()->getContents());

        $this->assertCount(1, $responseBodyContents->data, json_encode($responseBodyContents));
        $this->assertCount(1, $responseBodyContents->included, json_encode($responseBodyContents));
        $this->assertEquals(1, $responseBodyContents->included[0]->id);

        $response = $this->send(
            $this->request('GET', '/api/users', [
                'authenticatedAs' => 1
            ])->withQueryParams(['filter' => ['q' => 'group:4']])
        );
        $responseBodyContents = json_decode($response->getBody()->getContents());

        $this->assertCount(0, $responseBodyContents->data, json_encode($responseBodyContents));
        $this->assertObjectNotHasAttribute('included', $responseBodyContents, json_encode($responseBodyContents));
    }

    /**
     * @test
     */
    public function admin_can_see_hidden_groups()
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
        $response = $this->send(
            $this->request('GET', '/api/users', [
                'authenticatedAs' => 1
            ])->withQueryParams(['filter' => ['q' => 'group:99']])
        );
        $responseBodyContents = json_decode($response->getBody()->getContents());

        $this->assertCount(1, $responseBodyContents->data, json_encode($responseBodyContents));
        $this->assertCount(1, $responseBodyContents->included, json_encode($responseBodyContents));
        $this->assertEquals(99, $responseBodyContents->included[0]->id);
    }
}
