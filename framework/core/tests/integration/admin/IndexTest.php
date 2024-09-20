<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\admin;

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;

class IndexTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->prepareDatabase([
            User::class => [
                $this->normalUser()
            ]
        ]);
    }

    public function test_admin_can_access_admin_route(): void
    {
        $response = $this->send(
            $this->request('GET', '/admin', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_user_cannot_access_admin_route(): void
    {
        $response = $this->send(
            $this->request('GET', '/admin', [
                'authenticatedAs' => 2,
            ])
        );

        $this->assertEquals(403, $response->getStatusCode());
    }
}
