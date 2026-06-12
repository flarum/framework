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
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\Test;

class FoFSplitTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-split');

        $date = Carbon::parse('2021-01-01T12:00:00+00:00');

        $this->prepareDatabase([
            Discussion::class => [
                ['id' => 10, 'title' => 'A', 'created_at' => $date, 'last_posted_at' => $date, 'first_post_id' => 1, 'comment_count' => 4],
            ],
            Post::class => [
                ['id' => 1, 'number' => 1, 'discussion_id' => 10, 'created_at' => $date, 'type' => 'comment', 'content' => '<t><p>A</p></t>'],
                ['id' => 2, 'number' => 2, 'discussion_id' => 10, 'created_at' => $date, 'type' => 'comment', 'content' => '<t><p>B</p></t>', 'user_id' => 1],
                ['id' => 3, 'number' => 3, 'discussion_id' => 10, 'created_at' => $date, 'type' => 'comment', 'content' => '<t><p>C</p></t>'],
                ['id' => 4, 'number' => 4, 'discussion_id' => 10, 'created_at' => $date, 'type' => 'comment', 'content' => '<t><p>D</p></t>'],
            ],
        ]);
    }

    #[Test]
    public function split()
    {
        $response = $this->sendSuccessfulRequest('POST', '/api/split', [
            'json' => [
                'title' => 'Split',
                'start_post_id' => 2,
                'end_post_number' => 3,
            ],
        ]);

        $body = json_decode($response->getBody()->getContents(), true);
        $newDiscussionId = Arr::get($body, 'data.id');

        $this->assertLogExists('discussion.split_away', [
            'discussion_id' => 10,
            'new_discussion_id' => $newDiscussionId,
            'post_count' => 2,
        ]);

        $this->assertLogExists('discussion.split_into', [
            'discussion_id' => $newDiscussionId,
            'original_discussion_id' => 10,
            'post_count' => 2,
        ]);
    }
}
