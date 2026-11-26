<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\users;

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
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
            User::class => [
                $this->normalUser(),
            ],
        ]);
    }

    #[Test]
    public function disallows_index_for_guest()
    {
        $response = $this->send(
            $this->request('GET', '/api/users')
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    #[Test]
    public function shows_index_for_guest_when_they_have_permission()
    {
        $this->prepareDatabase([
            'group_permission' => [
                ['permission' => 'searchUsers', 'group_id' => 2],
            ],
        ]);

        $response = $this->send(
            $this->request('GET', '/api/users')
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function shows_index_for_admin()
    {
        $response = $this->send(
            $this->request('GET', '/api/users', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function shows_full_results_without_search_or_filter()
    {
        $response = $this->send(
            $this->request('GET', '/api/users', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true)['data'];
        $this->assertEqualsCanonicalizing(['1', '2'], Arr::pluck($data, 'id'));
    }

    #[Test]
    public function allows_last_seen_sorting_with_permission()
    {
        $this->prepareDatabase([
            'group_permission' => [
                ['permission' => 'searchUsers', 'group_id' => 2],
                ['permission' => 'user.viewLastSeenAt', 'group_id' => 2],
            ],
        ]);

        $response = $this->send(
            $this->request('GET', '/api/users')
            ->withQueryParams([
                'sort' => 'lastSeenAt',
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function disallows_last_seen_sorting_without_permission()
    {
        $this->prepareDatabase([
            'group_permission' => [
                ['permission' => 'searchUsers', 'group_id' => 2],
            ],
        ]);

        $response = $this->send(
            $this->request('GET', '/api/users')
                ->withQueryParams([
                    'sort' => 'lastSeenAt',
                ])
        );

        $this->assertEquals(400, $response->getStatusCode());
    }

    #[Test]
    public function group_filter_works()
    {
        $response = $this->send(
            $this->request('GET', '/api/users', [
                'authenticatedAs' => 1,
            ])->withQueryParams([
                'filter' => ['group' => '1'],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true)['data'];
        $this->assertEquals(['1'], Arr::pluck($data, 'id'));
    }

    #[Test]
    public function group_filter_works_negated()
    {
        $response = $this->send(
            $this->request('GET', '/api/users', [
                'authenticatedAs' => 1,
            ])->withQueryParams([
                'filter' => ['-group' => '1'],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true)['data'];
        $this->assertEquals(['2'], Arr::pluck($data, 'id'));
    }

    #[Test]
    public function email_filter_works()
    {
        $response = $this->send(
            $this->request('GET', '/api/users', [
                'authenticatedAs' => 1,
            ])->withQueryParams([
                'filter' => ['email' => 'admin@machine.local'],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true)['data'];
        $this->assertEquals(['1'], Arr::pluck($data, 'id'));
    }

    #[Test]
    public function email_filter_works_negated()
    {
        $response = $this->send(
            $this->request('GET', '/api/users', [
                'authenticatedAs' => 1,
            ])->withQueryParams([
                'filter' => ['-email' => 'admin@machine.local'],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true)['data'];
        $this->assertEquals(['2'], Arr::pluck($data, 'id'));
    }

    #[Test]
    public function email_filter_only_works_for_admin()
    {
        $response = $this->send(
            $this->request('GET', '/api/users', [
                'authenticatedAs' => 2,
            ])->withQueryParams([
                'filter' => ['email' => 'admin@machine.local'],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true)['data'];
        $this->assertEquals(['1', '2'], Arr::pluck($data, 'id'));
    }

    #[Test]
    public function the_next_link_of_a_numbered_page_leads_to_the_following_page()
    {
        $response = $this->send(
            $this->request('GET', '/api/users', [
                'authenticatedAs' => 1,
            ])->withQueryParams([
                'page' => ['number' => '1', 'limit' => '1'],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals(['1'], Arr::pluck($body['data'], 'id'));

        parse_str(parse_url($body['links']['next'] ?? '', PHP_URL_QUERY) ?? '', $query);

        $response = $this->send(
            $this->request('GET', '/api/users', [
                'authenticatedAs' => 1,
            ])->withQueryParams($query)
        );

        $this->assertEquals(200, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals(['2'], Arr::pluck($body['data'], 'id'));
    }

    #[Test]
    #[DataProvider('pageParametersGivenAsLists')]
    public function rejects_a_page_parameter_given_as_a_list(string $name)
    {
        $response = $this->send(
            $this->request('GET', '/api/users', [
                'authenticatedAs' => 1,
            ])->withQueryParams([
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

    #[Test]
    #[DataProvider('malformedPageParameters')]
    public function rejects_a_page_parameter_that_is_not_a_number(array $page, string $parameter)
    {
        $response = $this->send(
            $this->request('GET', '/api/users', [
                'authenticatedAs' => 1,
            ])->withQueryParams(['page' => $page])
        );

        $body = $response->getBody()->getContents();

        $this->assertEquals(400, $response->getStatusCode(), $body);
        $this->assertEquals($parameter, json_decode($body, true)['errors'][0]['source']['parameter'] ?? null);
    }

    public static function malformedPageParameters(): array
    {
        return [
            'limit with letters' => [['limit' => '2x'], 'page[limit]'],
            'offset with letters' => [['offset' => '2x'], 'page[offset]'],
            'number with letters' => [['number' => '2x'], 'page[number]'],
            'number zero' => [['number' => '0'], 'page[number]'],
            'offset as a list beside a number' => [['number' => '1', 'offset' => ['2']], 'page[offset]'],
            'offset too large to read' => [['offset' => '99999999999999999999'], 'page[offset]'],
            'number too large to read' => [['number' => '99999999999999999999'], 'page[number]'],
            'number too large to reach' => [['number' => (string) PHP_INT_MAX], 'page[number]'],
        ];
    }

    #[Test]
    #[DataProvider('listsWithALargestPage')]
    public function a_page_limit_too_large_to_read_gives_the_largest_page(string $path)
    {
        $response = $this->send(
            $this->request('GET', $path, [
                'authenticatedAs' => 1,
            ])->withQueryParams(['page' => ['limit' => '99999999999999999999']])
        );

        $body = $response->getBody()->getContents();

        $this->assertEquals(200, $response->getStatusCode(), $body);
        $this->assertEquals(50, json_decode($body, true)['meta']['page']['limit'] ?? null, $body);
    }

    public static function listsWithALargestPage(): array
    {
        return [
            'paginated' => ['/api/users'],
            'not paginated' => ['/api/groups'],
        ];
    }

    #[Test]
    #[DataProvider('pageLimitsBelowOne')]
    public function a_page_limit_must_be_a_positive_integer(string $limit)
    {
        $response = $this->send(
            $this->request('GET', '/api/users', [
                'authenticatedAs' => 1,
            ])->withQueryParams(['page' => ['limit' => $limit]])
        );

        $body = $response->getBody()->getContents();

        $this->assertEquals(400, $response->getStatusCode(), $body);
        $this->assertEquals('page[limit] must be a positive integer', json_decode($body, true)['errors'][0]['detail'] ?? null, $body);
    }

    public static function pageLimitsBelowOne(): array
    {
        return [
            'zero' => ['0'],
            'letters' => ['2x'],
        ];
    }
}
