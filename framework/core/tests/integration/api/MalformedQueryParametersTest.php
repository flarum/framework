<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api;

use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class MalformedQueryParametersTest extends TestCase
{
    #[Test]
    #[DataProvider('malformedQueries')]
    public function malformed_query_parameters_are_a_bad_request(string $path, array $query, string $parameter)
    {
        $response = $this->send(
            $this->request('GET', $path)->withQueryParams($query)
        );

        $body = (string) $response->getBody();

        $this->assertEquals(400, $response->getStatusCode(), $body);
        $this->assertEquals($parameter, json_decode($body, true)['errors'][0]['source']['parameter'] ?? null, $body);
    }

    public static function malformedQueries(): array
    {
        return [
            'nested include' => ['/api/discussions', ['include' => [['user']]], 'include'],
            'nested include with the defaults' => ['/api/posts', ['include' => [['user']], 'withDefaultIncludes' => '1'], 'include'],
            'array page limit' => ['/api/discussions', ['page' => ['limit' => ['5']]], 'page[limit]'],
            'array page offset' => ['/api/discussions', ['page' => ['offset' => ['5']]], 'page[offset]'],
            'overflowing page offset' => ['/api/discussions', ['page' => ['offset' => '99999999999999999999']], 'page[offset]'],
            'array fulltext search' => ['/api/discussions', ['filter' => ['q' => ['x']]], 'filter[q]'],
            'array page number' => ['/api/discussions', ['page' => ['number' => ['2']]], 'page[number]'],
            'non-digit page number' => ['/api/discussions', ['page' => ['number' => '2abc']], 'page[number]'],
            'negative page number' => ['/api/discussions', ['page' => ['number' => '-1']], 'page[number]'],
            'overflowing page number' => ['/api/discussions', ['page' => ['number' => '99999999999999999999']], 'page[number]'],
            'zero page number' => ['/api/discussions', ['page' => ['number' => '0']], 'page[number]'],
            'page number whose offset overflows' => ['/api/discussions', ['page' => ['number' => (string) (intdiv(PHP_INT_MAX, 2) + 2), 'limit' => '2']], 'page[number]'],
            'page number on a list without a limit' => ['/api/groups', ['page' => ['number' => 'abc']], 'page[number]'],
            'whitespace page number' => ['/api/discussions', ['page' => ['number' => ' ']], 'page[number]'],
        ];
    }

    #[Test]
    #[DataProvider('acceptedPageNumbers')]
    public function accepted_page_numbers_are_not_a_bad_request(string $path, array $query)
    {
        $response = $this->send(
            $this->request('GET', $path)->withQueryParams($query)
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
    }

    public static function acceptedPageNumbers(): array
    {
        return [
            'page number on a list without a limit' => ['/api/groups', ['page' => ['number' => '2']]],
            'empty page number' => ['/api/discussions', ['page' => ['number' => '']]],
        ];
    }
}
