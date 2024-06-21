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
use Flarum\User\User;
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
            User::class => [
                $this->normalUser(),
            ],
        ]);
    }

    /**
     * @test
     */
    public function disallows_index_for_guest()
    {
        $response = $this->send(
            $this->request('GET', '/api/users')
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function shows_index_for_guest_when_they_have_permission()
    {
        $this->prepareDatabase([
            'group_permission' => [
                ['permission' => 'searchUsers', 'group_id' => 2],
            ],
        ]);

        $response = $this->send(
            $this->request('GET', '/api/users')
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function shows_index_for_admin()
    {
        $response = $this->send(
            $this->request('GET', '/api/users', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function shows_full_results_without_search_or_filter()
    {
        $response = $this->send(
            $this->request('GET', '/api/users', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true)['data'];
        $this->assertEqualsCanonicalizing(['1', '2'], Arr::pluck($data, 'id'));
    }

    /**
     * @test
     */
    public function allows_last_seen_sorting_with_permission()
    {
        $this->prepareDatabase([
            'group_permission' => [
                ['permission' => 'searchUsers', 'group_id' => 2],
                ['permission' => 'user.viewLastSeenAt', 'group_id' => 2],
            ],
        ]);

        $response = $this->send(
            $this->request('GET', '/api/users')
            ->withQueryParams([
                'sort' => 'lastSeenAt',
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function disallows_last_seen_sorting_without_permission()
    {
        $this->prepareDatabase([
            'group_permission' => [
                ['permission' => 'searchUsers', 'group_id' => 2],
            ],
        ]);

        $response = $this->send(
            $this->request('GET', '/api/users')
                ->withQueryParams([
                    'sort' => 'lastSeenAt',
                ])
        );

        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function group_filter_works()
    {
        $response = $this->send(
            $this->request('GET', '/api/users', [
                'authenticatedAs' => 1,
            ])->withQueryParams([
                'filter' => ['group' => '1'],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true)['data'];
        $this->assertEquals(['1'], Arr::pluck($data, 'id'));
    }

    /**
     * @test
     */
    public function group_filter_works_negated()
    {
        $response = $this->send(
            $this->request('GET', '/api/users', [
                'authenticatedAs' => 1,
            ])->withQueryParams([
                'filter' => ['-group' => '1'],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true)['data'];
        $this->assertEquals(['2'], Arr::pluck($data, 'id'));
    }

    /**
     * @test
     */
    public function email_filter_works()
    {
        $response = $this->send(
            $this->request('GET', '/api/users', [
                'authenticatedAs' => 1,
            ])->withQueryParams([
                'filter' => ['email' => 'admin@machine.local'],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true)['data'];
        $this->assertEquals(['1'], Arr::pluck($data, 'id'));
    }

    /**
     * @test
     */
    public function email_filter_works_negated()
    {
        $response = $this->send(
            $this->request('GET', '/api/users', [
                'authenticatedAs' => 1,
            ])->withQueryParams([
                'filter' => ['-email' => 'admin@machine.local'],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true)['data'];
        $this->assertEquals(['2'], Arr::pluck($data, 'id'));
    }

    /**
     * @test
     */
    public function email_filter_only_works_for_admin()
    {
        $response = $this->send(
            $this->request('GET', '/api/users', [
                'authenticatedAs' => 2,
            ])->withQueryParams([
                'filter' => ['email' => 'admin@machine.local'],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true)['data'];
        $this->assertEquals(['1', '2'], Arr::pluck($data, 'id'));
    }
}
