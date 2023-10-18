<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Extend;
use Flarum\Post\CommentPost;
use Flarum\Search\IndexerInterface;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Assert;

class SearchIndexTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            'discussions' => [
                ['id' => 1, 'title' => 'DISCUSSION 1', 'created_at' => Carbon::now()->subDays(1)->toDateTimeString(), 'hidden_at' => null, 'comment_count' => 1, 'user_id' => 1, 'first_post_id' => 1],
                ['id' => 2, 'title' => 'DISCUSSION 2', 'created_at' => Carbon::now()->subDays(2)->toDateTimeString(), 'hidden_at' => Carbon::now(), 'comment_count' => 1, 'user_id' => 1],
            ],
            'posts' => [
                ['id' => 1, 'number' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 1, 'type' => 'comment', 'content' => '<r><p>content</p></r>', 'hidden_at' => null],
                ['id' => 2, 'number' => 2, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 1, 'type' => 'comment', 'content' => '<r><p>content</p></r>', 'hidden_at' => Carbon::now()],
            ],
        ]);
    }

    public static function modelProvider(): array
    {
        return [
            ['discussions', Discussion::class, 'title'],
            ['posts', CommentPost::class, 'content'],
        ];
    }

    /** @dataProvider modelProvider */
    public function test_indexer_triggered_on_create(string $type, string $modelClass, string $attribute)
    {
        $this->extend(
            (new Extend\SearchIndex())
                ->indexer($modelClass, TestIndexer::class)
        );

        // Create discussion
        $response = $this->send(
            $this->request('POST', "/api/$type", [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'attributes' => [
                            $attribute => 'test',
                        ],
                        'relationships' => ($type === 'posts' ? [
                            'discussion' => [
                                'data' => [
                                    'type' => 'discussions',
                                    'id' => 1,
                                ],
                            ],
                        ] : null),
                    ]
                ],
            ]),
        );

        Assert::assertEquals('save', TestIndexer::$triggered, $response->getBody()->getContents());
    }

    /** @dataProvider modelProvider */
    public function test_indexer_triggered_on_save(string $type, string $modelClass, string $attribute)
    {
        $this->extend(
            (new Extend\SearchIndex())
                ->indexer($modelClass, TestIndexer::class)
        );

        // Rename discussion
        $response = $this->send(
            $this->request('PATCH', "/api/$type/1", [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'attributes' => [
                            $attribute => 'changed'
                        ]
                    ]
                ],
            ]),
        );

        Assert::assertEquals('save', TestIndexer::$triggered, $response->getBody()->getContents());
    }

    /** @dataProvider modelProvider */
    public function test_indexer_triggered_on_delete(string $type, string $modelClass, string $attribute)
    {
        $this->extend(
            (new Extend\SearchIndex())
                ->indexer($modelClass, TestIndexer::class)
        );

        // Delete discussion
        $response = $this->send(
            $this->request('DELETE', "/api/$type/1", [
                'authenticatedAs' => 1,
                'json' => [],
            ]),
        );

        Assert::assertEquals('delete', TestIndexer::$triggered, $response->getBody()->getContents());
    }

    /** @dataProvider modelProvider */
    public function test_indexer_triggered_on_hide(string $type, string $modelClass, string $attribute)
    {
        $this->extend(
            (new Extend\SearchIndex())
                ->indexer($modelClass, TestIndexer::class)
        );

        // Hide discussion
        $response = $this->send(
            $this->request('PATCH', "/api/$type/1", [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'isHidden' => true
                        ]
                    ]
                ],
            ]),
        );

        Assert::assertEquals('delete', TestIndexer::$triggered, $response->getBody()->getContents());
    }

    /** @dataProvider modelProvider */
    public function test_indexer_triggered_on_restore(string $type, string $modelClass, string $attribute)
    {
        $this->extend(
            (new Extend\SearchIndex())
                ->indexer($modelClass, TestIndexer::class)
        );

        // Restore discussion
        $response = $this->send(
            $this->request('PATCH', "/api/$type/2", [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'isHidden' => false
                        ]
                    ]
                ],
            ]),
        );

        Assert::assertEquals('save', TestIndexer::$triggered, $response->getBody()->getContents());
    }

    protected function tearDown(): void
    {
        TestIndexer::$triggered = null;

        parent::tearDown();
    }
}

class TestIndexer implements IndexerInterface
{
    public static ?string $triggered = null;

    public static function index(): string
    {
        return 'test';
    }

    public function save(array $models): void
    {
        self::$triggered = 'save';
    }

    public function delete(array $models): void
    {
        self::$triggered = 'delete';
    }

    public function build(): void
    {
        //
    }

    public function flush(): void
    {
        //
    }
}
