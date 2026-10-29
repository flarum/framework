<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Tests\integration;

use Carbon\Carbon;
use Flarum\Audit\Tests\integration\InteractsWithAuditLog;
use Flarum\Discussion\Discussion;
use Flarum\Tags\Tag;
use Flarum\Testing\integration\TestCase;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\Test;

class AuditTest extends TestCase
{
    use InteractsWithAuditLog;

    public function setUp(): void
    {
        parent::setUp();

        $this->setUpAuditLog();

        $this->extension('flarum-audit', 'flarum-tags');

        $this->prepareDatabase([
            Discussion::class => [
                ['id' => 10, 'title' => 'A', 'created_at' => Carbon::parse('2021-01-01T12:00:00+00:00')],
            ],
            Tag::class => [
                ['id' => 1, 'name' => 'One', 'slug' => 'one'],
                ['id' => 2, 'name' => 'Two', 'slug' => 'two'],
            ],
            'discussion_tag' => [
                ['discussion_id' => 10, 'tag_id' => 1],
            ],
        ]);
    }

    #[Test]
    public function tagged()
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

    #[Test]
    public function create()
    {
        $response = $this->sendSuccessfulRequest('POST', '/api/tags', [
            'json' => [
                'data' => [
                    'attributes' => [
                        'name' => 'Three',
                        'slug' => 'three',
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

    #[Test]
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

    #[Test]
    public function delete()
    {
        $this->sendSuccessfulRequest('DELETE', '/api/tags/1', [], 204);

        $this->assertLogExists('tag.deleted', [
            'tag_id' => 1,
        ]);
    }
}
