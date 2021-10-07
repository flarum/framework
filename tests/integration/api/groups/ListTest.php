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

class ListTest extends TestCase
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
    public function shows_limited_index_for_guest()
    {
        $response = $this->send(
            $this->request('GET', '/api/groups')
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);

        // The four default groups created by the installer
        $this->assertEquals(['1', '2', '3', '4'], Arr::pluck($data['data'], 'id'));
    }

    /**
     * @test
     */
    public function shows_index_for_admin()
    {
        $response = $this->send(
            $this->request('GET', '/api/groups', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);

        // The four default groups created by the installer and our hidden group
        $this->assertEquals(['1', '2', '3', '4', '10'], Arr::pluck($data['data'], 'id'));
    }

    /**
     * @test
     */
    public function filters_only_public_groups_for_admin()
    {
        $response = $this->send(
            $this->request('GET', '/api/groups', [
                'authenticatedAs' => 1,
            ])
            ->withQueryParams([
                'filter' => ['hidden' => 0],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);

        // The four default groups created by the installer without our hidden group
        $this->assertEquals(['1', '2', '3', '4'], Arr::pluck($data['data'], 'id'));
    }

    /**
     * @test
     */
    public function filters_only_hidden_groups_for_admin()
    {
        $response = $this->send(
            $this->request('GET', '/api/groups', [
                'authenticatedAs' => 1,
            ])
            ->withQueryParams([
                'filter' => ['hidden' => 1],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);

        // Only our hidden group
        $this->assertEquals(['10'], Arr::pluck($data['data'], 'id'));
    }

    /**
     * @test
     */
    public function filters_only_public_groups_for_guest()
    {
        $response = $this->send(
            $this->request('GET', '/api/groups')
            ->withQueryParams([
                'filter' => ['hidden' => 0],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);

        // The four default groups created by the installer without our hidden group
        $this->assertEquals(['1', '2', '3', '4'], Arr::pluck($data['data'], 'id'));
    }

    /**
     * @test
     */
    public function hides_hidden_groups_when_filtering_for_guest()
    {
        $response = $this->send(
            $this->request('GET', '/api/groups')
            ->withQueryParams([
                'filter' => ['hidden' => 1],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);

        // When guest attempts to filter for hidden groups, system should
        // still apply scoping and exclude those groups from results
        $this->assertEquals([], Arr::pluck($data['data'], 'id'));
    }

    /**
     * @test
     */
    public function filters_groups_by_single_name()
    {
        $response = $this->send(
            $this->request('GET', '/api/groups')
            ->withQueryParams([
                'filter' => ['name' => 'Admin'],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);

        // Only admin group
        $this->assertEquals(['1'], Arr::pluck($data['data'], 'id'));
    }

    /**
     * @test
     */
    public function filters_groups_by_single_name_negated()
    {
        $response = $this->send(
            $this->request('GET', '/api/groups')
            ->withQueryParams([
                'filter' => ['-name' => 'Admin'],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);

        // ALl public groups but admin
        $this->assertEquals(['2', '3', '4'], Arr::pluck($data['data'], 'id'));
    }

    /**
     * @test
     */
    public function filters_groups_by_multiple_names()
    {
        $response = $this->send(
            $this->request('GET', '/api/groups')
            ->withQueryParams([
                'filter' => ['name' => 'Admin,Mods'],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);

        // Only admin & mod group
        $this->assertEquals(['1', '4'], Arr::pluck($data['data'], 'id'));
    }

    /**
     * @test
     */
    public function filters_groups_by_multiple_names_negated()
    {
        $response = $this->send(
            $this->request('GET', '/api/groups')
            ->withQueryParams([
                'filter' => ['-name' => 'Admin,Mods'],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);

        // ALl public groups but admin & mods
        $this->assertEquals(['2', '3'], Arr::pluck($data['data'], 'id'));
    }

    /**
     * @test
     */
    public function paginates_groups_without_filter()
    {
        $response = $this->send(
            $this->request('GET', '/api/groups')
            ->withQueryParams([
                'page' => ['limit' => '2', 'offset' => '2'],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);

        // Show second page of groups
        $this->assertEquals(['3', '4'], Arr::pluck($data['data'], 'id'));
    }

    /**
     * @test
     */
    public function paginates_groups_with_filter()
    {
        $response = $this->send(
            $this->request('GET', '/api/groups')
            ->withQueryParams([
                'filter' => ['-name' => 'Admin'],
                'page' => ['limit' => '2', 'offset' => '2'],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);

        // Show second page of groups
        $this->assertEquals(['4'], Arr::pluck($data['data'], 'id'));
    }


    /**
     * @test
     */
    public function sorts_groups_by_name()
    {
        $response = $this->send(
            $this->request('GET', '/api/groups', [
                'authenticatedAs' => 1,
            ])
            ->withQueryParams([
                'sort' => 'nameSingular',
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);

        // Ascending alphabetical order is: Admin - Guest - Hidden - Member - Mod
        $this->assertEquals(['1', '2', '10', '3', '4'], Arr::pluck($data['data'], 'id'));
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
