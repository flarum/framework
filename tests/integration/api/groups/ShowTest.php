<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\groups;

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
            'groups' => [
                $this->hiddenGroup(),
            ],
        ]);
    }

    /**
     * @test
     */
    public function shows_public_group_for_guest()
    {
        $response = $this->send(
            $this->request('GET', '/api/groups/1')
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);

        // Default group created by the installer should be returned
        $this->assertEquals('1', Arr::get($data, 'data.id'));
    }

    /**
     * @test
     */
    public function shows_public_group_for_admin()
    {
        $response = $this->send(
            $this->request('GET', '/api/groups/1', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);

        // Default group created by the installer should be returned
        $this->assertEquals('1', Arr::get($data, 'data.id'));
    }

    /**
     * @test
     */
    public function hides_hidden_group_for_guest()
    {
        $response = $this->send(
            $this->request('GET', '/api/groups/10')
        );

        // Hidden group should not be returned for guest
        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function shows_hidden_group_for_admin()
    {
        $response = $this->send(
            $this->request('GET', '/api/groups/10', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);

        // Hidden group should be returned for admin
        $this->assertEquals('10', Arr::get($data, 'data.id'));
    }

    /**
     * @test
     */
    public function rejects_request_for_non_existing_group()
    {
        $response = $this->send(
            $this->request('GET', '/api/groups/999', [
                'authenticatedAs' => 1,
            ])
        );

        // If group does not exist in database, controller
        // should reject the request with 404 Not found
        $this->assertEquals(404, $response->getStatusCode());
    }

    protected function hiddenGroup(): array
    {
        return [
            'id' => 10,
            'name_singular' => 'Hidden',
            'name_plural' => 'Ninjas',
            'color' => null,
            'icon' => 'fas fa-wrench',
            'is_hidden' => 1
        ];
    }
}
