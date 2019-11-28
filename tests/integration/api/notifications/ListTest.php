<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\notifications;

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
                $this->normalUser(),
            ],
            'access_tokens' => [
                ['token' => 'normaltoken', 'user_id' => 2],
            ],
        ]);
    }

    /**
     * @test
     */
    public function disallows_index_for_guest()
    {
        $response = $this->send(
            $this->request('GET', '/api/notifications')
        );

        $this->assertEquals(401, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function shows_index_for_user()
    {
        $response = $this->send(
            $this->request('GET', '/api/notifications')
                ->withHeader('Authorization', 'Token normaltoken')
        );

        $this->assertEquals(200, $response->getStatusCode());
    }
}
