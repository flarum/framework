<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\forum;

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Illuminate\Support\Arr;

class ShowTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * @inheritDoc
     */
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
    public function guest_user_does_not_see_actor_relationship()
    {
        $response = $this->send(
            $this->request('GET', '/api')
        );

        $this->assertEquals(200, $response->getStatusCode());

        $json = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayNotHasKey('actor', Arr::get($json, 'data.relationships'));
    }

    /**
     * @test
     */
    public function normal_user_sees_most_information()
    {
        $response = $this->send(
            $this->request('GET', '/api', [
                'authenticatedAs' => 2,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $json = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals(true, Arr::get($json, 'data.attributes.debug'));
        $this->assertEquals('http://localhost', Arr::get($json, 'data.attributes.baseUrl'));
        $this->assertEquals('http://localhost/api', Arr::get($json, 'data.attributes.apiUrl'));

        $this->assertArrayNotHasKey('adminUrl', Arr::get($json, 'data.attributes'));
        $this->assertArrayHasKey('actor', Arr::get($json, 'data.relationships'));
        $this->assertEquals(2, Arr::get($json, 'data.relationships.actor.data.id'));
    }

    /**
     * @test
     */
    public function admin_user_sees_even_more()
    {
        $response = $this->send(
            $this->request('GET', '/api', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $json = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals(true, Arr::get($json, 'data.attributes.debug'));
        $this->assertEquals('http://localhost', Arr::get($json, 'data.attributes.baseUrl'));
        $this->assertEquals('http://localhost/api', Arr::get($json, 'data.attributes.apiUrl'));
        $this->assertEquals('http://localhost/admin', Arr::get($json, 'data.attributes.adminUrl'));
    }
}
