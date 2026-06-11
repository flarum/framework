<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Tests\integration;

use Carbon\Carbon;

class FlarumTagsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags');

        $this->prepareDatabase([
            'discussions' => [
                ['id' => 10, 'title' => 'A', 'created_at' => Carbon::parse('2021-01-01T12:00:00+00:00')],
            ],
            'tags' => [
                ['id' => 1, 'name' => 'One', 'slug' => 'one'],
                ['id' => 2, 'name' => 'Two', 'slug' => 'two'],
            ],
            'discussion_tag' => [
                ['discussion_id' => 10, 'tag_id' => 1],
            ],
        ]);
    }

    /**
     * @test
     */
    public function tag()
    {
        $this->sendSuccessfulRequest('PATCH', '/api/discussions/10', [
            'json' => [
                'data' => [
                    'relationships' => [
                        'tags' => [
                            'data' => [
                                [
                                    'type' => 'tags',
                                    'id' => '2',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertLogExists('discussion.tagged', [
            'discussion_id' => 10,
            'old_tags' => ['one'],
            'new_tags' => ['two'],
        ]);
    }
}
