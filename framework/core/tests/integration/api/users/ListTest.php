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
            'users' => [
                $this->normalUser(),
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

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @test
     */
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

    /**
     * @test
     */
    public function shows_index_for_admin()
    {
        $response = $this->send(
            $this->request('GET', '/api/users', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function shows_full_results_without_search_or_filter()
    {
        $response = $this->send(
            $this->request('GET', '/api/users', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true)['data'];
        $this->assertEquals(['1', '2'], Arr::pluck($data, 'id'));
    }

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
    public function group_gambit_works()
    {
        $response = $this->send(
            $this->request('GET', '/api/users', [
                'authenticatedAs' => 1,
            ])->withQueryParams([
                'filter' => ['q' => 'group:1'],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true)['data'];
        $this->assertEquals(['1'], Arr::pluck($data, 'id'));
    }

    /**
     * @test
     */
    public function group_gambit_works_negated()
    {
        $response = $this->send(
            $this->request('GET', '/api/users', [
                'authenticatedAs' => 1,
            ])->withQueryParams([
                'filter' => ['q' => '-group:1'],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true)['data'];
        $this->assertEquals(['2'], Arr::pluck($data, 'id'));
    }

    /**
     * @test
     */
    public function email_gambit_works()
    {
        $response = $this->send(
            $this->request('GET', '/api/users', [
                'authenticatedAs' => 1,
            ])->withQueryParams([
                'filter' => ['q' => 'email:admin@machine.local'],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true)['data'];
        $this->assertEquals(['1'], Arr::pluck($data, 'id'));
    }

    /**
     * @test
     */
    public function email_gambit_works_negated()
    {
        $response = $this->send(
            $this->request('GET', '/api/users', [
                'authenticatedAs' => 1,
            ])->withQueryParams([
                'filter' => ['q' => '-email:admin@machine.local'],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true)['data'];
        $this->assertEquals(['2'], Arr::pluck($data, 'id'));
    }

    /**
     * @test
     */
    public function email_gambit_only_works_for_admin()
    {
        $response = $this->send(
            $this->request('GET', '/api/users', [
                'authenticatedAs' => 2,
            ])->withQueryParams([
                'filter' => ['q' => 'email:admin@machine.local'],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true)['data'];
        $this->assertEquals([], Arr::pluck($data, 'id'));
    }

    /**
     * A search with a null query (rather than a string) must not trip the
     * `str_getcsv(): Passing null ...` deprecation on PHP 8.1+, which escalates
     * to a fatal on stricter setups. Regression test for the gambit search
     * choking on a null `q`.
     *
     * The handler only throws for the "passing null" deprecation so it is not
     * derailed by unrelated deprecations emitted during the request — including
     * PHP 8.5's separate deprecation of str_getcsv()'s default $escape argument.
     *
     * @test
     */
    public function search_with_null_query_does_not_emit_deprecation()
    {
        set_error_handler(function ($errno, $errstr) {
            if (strpos($errstr, 'Passing null') !== false) {
                throw new \ErrorException($errstr, 0, $errno);
            }

            return false;
        }, E_DEPRECATED);

        try {
            $response = $this->send(
                $this->request('GET', '/api/users', [
                    'authenticatedAs' => 1,
                ])->withQueryParams([
                    'filter' => ['q' => null],
                ])
            );
        } finally {
            restore_error_handler();
        }

        $this->assertEquals(200, $response->getStatusCode());
    }
}
