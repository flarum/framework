<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Tests\integration\thirdparty;

use Carbon\Carbon;
use Flarum\Audit\Tests\integration\TestCase;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use PHPUnit\Framework\Attributes\Test;

class FoFMergeTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-merge-discussions');

        $date = Carbon::parse('2021-01-01T12:00:00+00:00');

        $this->prepareDatabase([
            Discussion::class => [
                ['id' => 10, 'title' => 'A', 'created_at' => $date, 'last_posted_at' => $date, 'first_post_id' => 1, 'comment_count' => 1],
                ['id' => 11, 'title' => 'B', 'created_at' => $date, 'last_posted_at' => $date, 'first_post_id' => 2, 'comment_count' => 1],
                ['id' => 12, 'title' => 'C', 'created_at' => $date, 'last_posted_at' => $date, 'first_post_id' => 3, 'comment_count' => 2],
            ],
            Post::class => [
                ['id' => 1, 'number' => 1, 'discussion_id' => 10, 'created_at' => $date, 'type' => 'comment', 'content' => '<t><p>A</p></t>'],
                ['id' => 2, 'number' => 1, 'discussion_id' => 11, 'created_at' => $date, 'type' => 'comment', 'content' => '<t><p>B</p></t>'],
                ['id' => 3, 'number' => 1, 'discussion_id' => 12, 'created_at' => $date, 'type' => 'comment', 'content' => '<t><p>C1</p></t>'],
                ['id' => 4, 'number' => 2, 'discussion_id' => 12, 'created_at' => $date, 'type' => 'comment', 'content' => '<t><p>C2</p></t>'],
            ],
        ]);
    }

    #[Test]
    public function mergeSingle()
    {
        $this->sendSuccessfulRequest('POST', '/api/discussions/10/merge', [
            'json' => [
                'ids' => '11',
            ],
        ]);

        $this->assertLogExists('discussion.merged_into', [
            'discussion_id' => 10,
            'original_discussion_ids' => [11],
            'post_count' => 1,
        ]);

        $this->assertLogExists('discussion.merged_away', [
            'discussion_id' => 11,
            'new_discussion_id' => 10,
        ]);
    }

    #[Test]
    public function mergeMultiple()
    {
        $this->sendSuccessfulRequest('POST', '/api/discussions/10/merge', [
            'json' => [
                'ids' => ['11', '12'],
            ],
        ]);

        $this->assertLogExists('discussion.merged_into', [
            'discussion_id' => 10,
            'original_discussion_ids' => [11, 12],
            'post_count' => 3,
        ]);

        $this->assertLogExists('discussion.merged_away', [
            'discussion_id' => 11,
            'new_discussion_id' => 10,
        ]);

        $this->assertLogExists('discussion.merged_away', [
            'discussion_id' => 12,
            'new_discussion_id' => 10,
        ], 1, 1);
    }
}
