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

class ListTest extends TestCase
{
    use RetrievesAuthorizedUsers;

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
                ['permission' => 'viewUserList', 'group_id' => 2],
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
    public function disallows_last_seen_sorting_without_permission()
    {
        $this->prepareDatabase([
            'group_permission' => [
                ['permission' => 'viewUserList', 'group_id' => 2],
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
    public function allows_last_seen_sorting_with_permission()
    {
        $this->prepareDatabase([
            'group_permission' => [
                ['permission' => 'viewUserList', 'group_id' => 2],
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
}
