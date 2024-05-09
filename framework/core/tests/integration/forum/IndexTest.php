<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\forum;

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

    /**
     * @test
     */
    public function guest_not_serialized_by_current_user_serializer()
    {
        $response = $this->send(
            $this->request('GET', '/')
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringNotContainsString('preferences', $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function user_serialized_by_current_user_serializer()
    {
        $response = $this->send(
            $this->request('GET', '/', [
                'authenticatedAs' => 2,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('preferences', $response->getBody()->getContents());
    }
}
