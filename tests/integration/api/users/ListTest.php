<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\users;

use Flarum\Tests\integration\RetrievesAuthorizedUsers;
use Flarum\Tests\integration\TestCase;
use Flarum\User\User;

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
            ],
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

        $this->assertEquals(401, $response->getStatusCode());
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
