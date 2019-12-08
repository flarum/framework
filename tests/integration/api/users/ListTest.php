<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\users;

use Flarum\Group\Permission;
use Flarum\Tests\integration\RetrievesAuthorizedUsers;
use Flarum\Tests\integration\TestCase;

class ListTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    public function setUp()
    {
        parent::setUp();

        $this->prepareDatabase([
            'users' => [
                $this->adminUser(),
            ],
            'groups' => [
                $this->adminGroup(),
                $this->guestGroup(),
            ],
            'group_permission' => [],
            'group_user' => [
                ['user_id' => 1, 'group_id' => 1],
            ],
            'access_tokens' => [
                ['token' => 'admintoken', 'user_id' => 1],
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
        Permission::unguarded(function () {
            Permission::create([
                'permission' => 'viewUserList',
                'group_id' => 2,
            ]);
        });

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
            $this->request('GET', '/api/users')
                ->withHeader('Authorization', 'Token admintoken')
        );

        $this->assertEquals(200, $response->getStatusCode());
    }
}
