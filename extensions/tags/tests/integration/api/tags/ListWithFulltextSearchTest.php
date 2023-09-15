<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Tests\integration\api\tags;

use Flarum\Testing\integration\TestCase;
use Illuminate\Support\Arr;

class ListWithFulltextSearchTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags');

        $this->prepareDatabase([
            'tags' => [
                ['id' => 2, 'name' => 'Acme', 'slug' => 'acme'],
                ['id' => 3, 'name' => 'Test', 'slug' => 'test'],
                ['id' => 4, 'name' => 'Tag', 'slug' => 'tag'],
                ['id' => 5, 'name' => 'Franz', 'slug' => 'franz'],
                ['id' => 6, 'name' => 'Software', 'slug' => 'software'],
                ['id' => 7, 'name' => 'Laravel', 'slug' => 'laravel'],
                ['id' => 8, 'name' => 'Flarum', 'slug' => 'flarum'],
                ['id' => 9, 'name' => 'Tea', 'slug' => 'tea'],
                ['id' => 10, 'name' => 'Access', 'slug' => 'access'],
            ],
        ]);
    }

    /**
     * @dataProvider searchDataProvider
     * @test
     */
    public function can_search_for_tags(string $search, array $expected)
    {
        $response = $this->send(
            $this->request('GET', '/api/tags')->withQueryParams([
                'filter' => [
                    'q' => $search,
                ],
            ])
        );

        $data = json_decode($response->getBody()->getContents(), true)['data'];

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($expected, Arr::pluck($data, 'id'));
    }

    public function searchDataProvider(): array
    {
        return [
            ['fla', [8]],
            ['flarum', [8]],
            ['flarums', []],
            ['a', [2, 10]],
            ['ac', [2, 10]],
            ['ace', []],
            ['acm', [2]],
            ['acmes', []],
            ['t', [3, 4, 9]],
            ['te', [3, 9]],
            ['test', [3]],
            ['tag', [4]],
            ['franz', [5]],
            ['software', [6]],
            ['lar', [7]],
            ['laravel', [7]],
            ['tea', [9]],
            ['access', [10]],
        ];
    }
}
