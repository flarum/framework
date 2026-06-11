<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Tests\integration;

use Illuminate\Support\Arr;

class FlarumTagsAdminTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags');

        $this->prepareDatabase([
            'tags' => [
                ['id' => 1, 'name' => 'One', 'slug' => 'one'],
            ],
        ]);
    }

    /**
     * @test
     */
    public function create()
    {
        $response = $this->sendSuccessfulRequest('POST', '/api/tags', [
            'json' => [
                'data' => [
                    'attributes' => [
                        'name' => 'Two',
                        'slug' => 'two',
                        'description' => '',
                        'color' => '#000',
                    ],
                ],
            ],
        ], 201);

        $body = json_decode($response->getBody()->getContents(), true);

        $this->assertLogExists('tag.created', [
            'tag_id' => Arr::get($body, 'data.id'),
        ]);
    }

    /**
     * @test
     */
    public function update()
    {
        $this->sendSuccessfulRequest('PATCH', '/api/tags/1', [
            'json' => [
                'data' => [
                    'attributes' => [
                        'name' => 'One One',
                    ],
                ],
            ],
        ]);

        $this->assertLogExists('tag.updated', [
            'tag_id' => 1,
        ]);
    }

    /**
     * @test
     */
    public function delete()
    {
        $this->sendSuccessfulRequest('DELETE', '/api/tags/1', [], 204);

        $this->assertLogExists('tag.deleted', [
            'tag_id' => 1,
        ]);
    }
}
