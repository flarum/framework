<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\groups;

use Flarum\Api\Endpoint\Index;
use Flarum\Api\Resource\GroupResource;
use Flarum\Extend;
use Flarum\Group\Group;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

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
            Group::class => [
                $this->hiddenGroup(),
            ],
        ]);
    }

    #[Test]
    public function shows_limited_index_for_guest()
    {
        $response = $this->send(
            $this->request('GET', '/api/groups')
        );

        $body = $response->getBody()->getContents();

        $this->assertEquals(200, $response->getStatusCode(), $body);
        $data = json_decode($body, true);

        // The four default groups created by the installer
        $this->assertEquals(['1', '2', '3', '4'], Arr::pluck($data['data'], 'id'));
    }

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
    public function links_to_the_next_page_when_a_limit_is_requested()
    {
        $response = $this->send(
            $this->request('GET', '/api/groups')
            ->withQueryParams([
                'page' => ['limit' => '2'],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals(['1', '2'], Arr::pluck($data['data'], 'id'));
        $this->assertStringContainsString('page%5Boffset%5D=2', $data['links']['next'] ?? '');
    }

    #[Test]
    public function pages_from_an_offset_without_a_limit()
    {
        $response = $this->send(
            $this->request('GET', '/api/groups')
            ->withQueryParams([
                'page' => ['offset' => '2'],
            ])
        );

        $body = $response->getBody()->getContents();

        $this->assertEquals(200, $response->getStatusCode(), $body);
        $data = json_decode($body, true);

        $this->assertEquals(['3', '4'], Arr::pluck($data['data'], 'id'));
        $this->assertStringContainsString('page%5Blimit%5D=2', $data['links']['prev'] ?? '');
    }

    #[Test]
    public function links_back_to_pages_of_the_same_size_when_paging_from_an_offset()
    {
        $response = $this->send(
            $this->request('GET', '/api/groups')
            ->withQueryParams([
                'page' => ['offset' => '2'],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);

        // Without the limit, the first page would be the whole, unpaginated list.
        $this->assertStringContainsString('page%5Blimit%5D=50', $data['links']['first'] ?? '');
    }

    #[Test]
    public function returns_every_group_when_the_endpoint_extracts_no_limit()
    {
        $this->extend(
            (new Extend\ApiResource(GroupResource::class))
                ->endpoint(Index::class, function (Index $endpoint): Index {
                    return $endpoint->extractLimit(fn () => null);
                })
        );

        $response = $this->send(
            $this->request('GET', '/api/groups')
            ->withQueryParams([
                'page' => ['limit' => '2'],
            ])
        );

        $body = $response->getBody()->getContents();

        $this->assertEquals(200, $response->getStatusCode(), $body);
        $data = json_decode($body, true);

        $this->assertEquals(['1', '2', '3', '4'], Arr::pluck($data['data'], 'id'));
    }

    #[Test]
    public function links_to_the_next_page_when_the_endpoint_has_a_default_limit()
    {
        $this->extend(
            (new Extend\ApiResource(GroupResource::class))
                ->endpoint(Index::class, function (Index $endpoint): Index {
                    return $endpoint->limit(2);
                })
        );

        $response = $this->send(
            $this->request('GET', '/api/groups')
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals(['1', '2'], Arr::pluck($data['data'], 'id'));
        $this->assertStringContainsString('page%5Boffset%5D=2', $data['links']['next'] ?? '');
    }

    #[Test]
    public function pages_with_the_default_limit_when_the_page_parameter_is_not_a_family()
    {
        $this->extend(
            (new Extend\ApiResource(GroupResource::class))
                ->endpoint(Index::class, function (Index $endpoint): Index {
                    return $endpoint->limit(2);
                })
        );

        $response = $this->send(
            $this->request('GET', '/api/groups')
            ->withQueryParams([
                'page' => 'foo',
            ])
        );

        $body = $response->getBody()->getContents();

        $this->assertEquals(200, $response->getStatusCode(), $body);
        $data = json_decode($body, true);

        $this->assertEquals(['1', '2'], Arr::pluck($data['data'], 'id'));
    }

    #[Test]
    public function returns_every_group_from_an_offset_when_the_endpoint_extracts_no_limit()
    {
        $this->extend(
            (new Extend\ApiResource(GroupResource::class))
                ->endpoint(Index::class, function (Index $endpoint): Index {
                    return $endpoint->extractLimit(fn () => null);
                })
        );

        $response = $this->send(
            $this->request('GET', '/api/groups')
            ->withQueryParams([
                'page' => ['offset' => '2'],
            ])
        );

        $body = $response->getBody()->getContents();

        $this->assertEquals(200, $response->getStatusCode(), $body);
        $data = json_decode($body, true);

        $this->assertEquals(['1', '2', '3', '4'], Arr::pluck($data['data'], 'id'));
    }

    #[Test]
    public function pages_by_number_without_a_limit()
    {
        $response = $this->send(
            $this->request('GET', '/api/groups')
            ->withQueryParams([
                'page' => ['number' => '2'],
            ])
        );

        $body = $response->getBody()->getContents();

        $this->assertEquals(200, $response->getStatusCode(), $body);
        $data = json_decode($body, true);

        $this->assertEquals([], $data['data']);
        $this->assertEquals(50, $data['meta']['page']['limit'] ?? null);
        $this->assertEquals(50, $data['meta']['page']['offset'] ?? null);
    }

    #[Test]
    #[DataProvider('pagesFromTheStart')]
    public function returns_every_group_when_asked_to_start_from_the_beginning(array $page)
    {
        $response = $this->send(
            $this->request('GET', '/api/groups')
            ->withQueryParams(['page' => $page])
        );

        $body = $response->getBody()->getContents();

        $this->assertEquals(200, $response->getStatusCode(), $body);
        $data = json_decode($body, true);

        $this->assertEquals(['1', '2', '3', '4'], Arr::pluck($data['data'], 'id'));
        $this->assertArrayNotHasKey('limit', $data['meta']['page'] ?? [], 'The whole list, not a page of it');
    }

    public static function pagesFromTheStart(): array
    {
        return [
            'offset zero' => [['offset' => '0']],
            'offset zero as an integer' => [['offset' => 0]],
            'offset zero padded' => [['offset' => '00']],
            'first page by number' => [['number' => '1']],
            'first page by number as an integer' => [['number' => 1]],
            'first page by number padded' => [['number' => '01']],
            'empty limit' => [['limit' => '']],
        ];
    }

    #[Test]
    public function links_back_to_pages_of_the_same_size_when_the_limit_is_empty()
    {
        $response = $this->send(
            $this->request('GET', '/api/groups')
            ->withQueryParams([
                'page' => ['limit' => '', 'offset' => '2'],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);

        $this->assertStringContainsString('page%5Blimit%5D=50', $data['links']['first'] ?? '');
    }

    #[Test]
    public function the_next_link_of_a_numbered_page_leads_to_the_following_page()
    {
        $response = $this->send(
            $this->request('GET', '/api/groups')
            ->withQueryParams([
                'page' => ['number' => '1', 'limit' => '2'],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals(['1', '2'], Arr::pluck($data['data'], 'id'));

        parse_str(parse_url($data['links']['next'] ?? '', PHP_URL_QUERY) ?? '', $query);

        $response = $this->send(
            $this->request('GET', '/api/groups')->withQueryParams($query)
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals(['3', '4'], Arr::pluck($data['data'], 'id'));
    }

    #[Test]
    public function returns_every_group_without_pagination_links_when_no_page_is_requested()
    {
        $response = $this->send(
            $this->request('GET', '/api/groups')
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals(['1', '2', '3', '4'], Arr::pluck($data['data'], 'id'));
        $this->assertArrayNotHasKey('next', $data['links'] ?? []);
    }

    #[Test]
    public function paginates_groups_with_filter()
    {
        $response = $this->send(
            $this->request('GET', '/api/groups')
            ->withQueryParams([
                'filter' => ['hidden' => 1],
                'page' => ['limit' => '1', 'offset' => '1'],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);

        // Show second page of groups. Because there is only one hidden group,
        // second page should be empty.
        $this->assertEmpty($data['data']);
    }

    #[Test]
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

    #[Test]
    #[DataProvider('pageParametersGivenAsLists')]
    public function rejects_a_page_parameter_given_as_a_list(string $name)
    {
        $response = $this->send(
            $this->request('GET', '/api/groups')->withQueryParams([
                'page' => [$name => ['1']],
            ])
        );

        $body = $response->getBody()->getContents();

        $this->assertEquals(400, $response->getStatusCode(), $body);
        $this->assertEquals("page[$name]", json_decode($body, true)['errors'][0]['source']['parameter'] ?? null);
    }

    public static function pageParametersGivenAsLists(): array
    {
        return [
            'limit' => ['limit'],
            'offset' => ['offset'],
            'number' => ['number'],
        ];
    }
}
