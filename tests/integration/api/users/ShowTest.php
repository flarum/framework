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

class ShowTest extends TestCase
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

    /**
     * @test
     */
    public function admin_can_see_user()
    {
        $response = $this->send(
            $this->request('GET', '/api/users/2', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function admin_can_see_user_via_slug()
    {
        $response = $this->send(
            $this->request('GET', '/api/users/normal', [
                'authenticatedAs' => 1,
            ])->withQueryParams([
                'bySlug' => true
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function guest_cannot_see_user()
    {
        $response = $this->send(
            $this->request('GET', '/api/users/2')
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function guest_cannot_see_user_by_slug()
    {
        $response = $this->send(
            $this->request('GET', '/api/users/2')->withQueryParams([
                'bySlug' => true
            ])
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function user_can_see_themselves()
    {
        $response = $this->send(
            $this->request('GET', '/api/users/2', [
                'authenticatedAs' => 2,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function user_can_see_themselves_via_slug()
    {
        $response = $this->send(
            $this->request('GET', '/api/users/normal', [
                'authenticatedAs' => 2,
            ])->withQueryParams([
                'bySlug' => true
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function user_cant_see_others_by_default()
    {
        $response = $this->send(
            $this->request('GET', '/api/users/1', [
                'authenticatedAs' => 2,
            ])
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function user_cant_see_others_by_default_via_slug()
    {
        $response = $this->send(
            $this->request('GET', '/api/users/admin', [
                'authenticatedAs' => 2,
            ])->withQueryParams([
                'bySlug' => true
            ])
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function user_can_see_others_if_allowed()
    {
        $this->prepareDatabase([
            'group_permission' => [
                ['permission' => 'viewDiscussions', 'group_id' => 3],
            ]
        ]);

        $response = $this->send(
            $this->request('GET', '/api/users/1', [
                'authenticatedAs' => 2,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function user_can_see_others_if_allowed_via_slug()
    {
        $this->prepareDatabase([
            'group_permission' => [
                ['permission' => 'viewDiscussions', 'group_id' => 3],
            ]
        ]);

        $response = $this->send(
            $this->request('GET', '/api/users/admin', [
                'authenticatedAs' => 2,
            ])->withQueryParams([
                'bySlug' => true
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
    }
}
