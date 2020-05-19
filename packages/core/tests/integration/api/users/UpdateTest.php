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

class UpdateTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
            ]
        ]);
    }

    /**
     * @test
     */
    public function users_can_see_their_private_information()
    {
        $response = $this->send(
            $this->request('PATCH', '/api/users/2', [
                'authenticatedAs' => 2,
                'json' => [],
            ])
        );

        // Test for successful response and that the email is included in the response
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('normal@machine.local', (string) $response->getBody());
    }

    /**
     * @test
     */
    public function users_can_not_see_other_users_private_information()
    {
        $response = $this->send(
            $this->request('PATCH', '/api/users/1', [
                'authenticatedAs' => 2,
                'json' => [],
            ])
        );

        // Make sure sensitive information is not made public
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotContains('admin@machine.local', (string) $response->getBody());
    }
}
