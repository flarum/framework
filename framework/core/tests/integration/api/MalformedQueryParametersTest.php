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
        ];
    }
}
