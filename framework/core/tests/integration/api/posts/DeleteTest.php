<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\posts;

use Carbon\Carbon;
use Flarum\Discussion\UserState;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;

class DeleteTest extends TestCase
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
                ['id' => 3, 'username' => 'acme', 'email' => 'acme@machine.local', 'is_email_confirmed' => 1],
                ['id' => 4, 'username' => 'acme2', 'email' => 'acme2@machine.local', 'is_email_confirmed' => 1],
            ],
            'discussions' => [
                ['id' => 3, 'title' => __CLASS__, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 5, 'last_post_number' => 5, 'last_post_id' => 10],
            ],
            'posts' => [
                ['id' => 5, 'discussion_id' => 3, 'created_at' => Carbon::createFromDate(1975, 5, 21)->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>foo bar</p></t>', 'number' => 1],
                ['id' => 6, 'discussion_id' => 3, 'created_at' => Carbon::createFromDate(1975, 5, 21)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>foo bar</p></t>', 'number' => 2],
                ['id' => 7, 'discussion_id' => 3, 'created_at' => Carbon::createFromDate(1975, 5, 21)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>foo bar</p></t>', 'number' => 3],
                ['id' => 8, 'discussion_id' => 3, 'created_at' => Carbon::createFromDate(1975, 5, 21)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>foo bar</p></t>', 'number' => 4],
                ['id' => 9, 'discussion_id' => 3, 'created_at' => Carbon::createFromDate(1975, 5, 21)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>foo bar</p></t>', 'number' => 5],
                ['id' => 10, 'discussion_id' => 3, 'created_at' => Carbon::createFromDate(1975, 5, 21)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>foo bar</p></t>', 'number' => 6],
            ],
            'discussion_user' => [
                ['discussion_id' => 3, 'user_id' => 2, 'last_read_post_number' => 6],
                ['discussion_id' => 3, 'user_id' => 4, 'last_read_post_number' => 3],
            ]
        ]);
    }

    /**
     * @dataProvider deleteLastPostsProvider
     * @test
     */
    public function deleting_last_posts_syncs_discussion_state_for_other_users(array $postIds, int $newLastReadNumber, int $userId)
    {
        // Delete the last post.
        foreach ($postIds as $postId) {
            $this->send(
                $this->request('DELETE', '/api/posts/'.$postId, ['authenticatedAs' => 1])
            );
        }

        // User 2 should now have last_read_post_number set to the new last one
        $this->assertEquals(
            $newLastReadNumber,
            UserState::query()
                ->where('discussion_id', 3)
                ->where('user_id', $userId)
                ->first()
                ->last_read_post_number
        );
    }

    public function deleteLastPostsProvider(): array
    {
        return [
            // User 2
            [[10], 5, 2],
            [[9, 10], 4, 2],
            [[10, 9, 8], 3, 2],
            [[8, 9, 10], 3, 2],
            [[7, 8, 9, 10], 2, 2],

            // User 4
            [[10], 3, 4],
            [[9, 10], 3, 4],
            [[10, 9, 8], 3, 4],
            [[8, 9, 10], 3, 4],
            [[10, 9, 8, 7], 2, 4],
            [[7, 8, 9, 10], 2, 4],
        ];
    }
}
