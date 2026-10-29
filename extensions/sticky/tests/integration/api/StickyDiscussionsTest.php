<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Sticky\Tests\integration\api;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Group\Group;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class StickyDiscussionsTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-sticky');

        $this->prepareDatabase([
            User::class => [
                ['id' => 1, 'username' => 'Muralf', 'email' => 'muralf@machine.local', 'is_email_confirmed' => 1],
                $this->normalUser(),
                ['id' => 3, 'username' => 'Muralf_', 'email' => 'muralf_@machine.local', 'is_email_confirmed' => 1],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => __CLASS__, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1, 'is_sticky' => true, 'last_post_number' => 1],
                ['id' => 2, 'title' => __CLASS__, 'created_at' => Carbon::now()->addMinutes(2), 'last_posted_at' => Carbon::now()->addMinutes(5), 'user_id' => 1, 'first_post_id' => 2, 'comment_count' => 1, 'is_sticky' => false, 'last_post_number' => 1],
                ['id' => 3, 'title' => __CLASS__, 'created_at' => Carbon::now()->addMinutes(3), 'last_posted_at' => Carbon::now()->addMinute(), 'user_id' => 1, 'first_post_id' => 3, 'comment_count' => 1, 'is_sticky' => true, 'last_post_number' => 1],
                ['id' => 4, 'title' => __CLASS__, 'created_at' => Carbon::now()->addMinutes(4), 'last_posted_at' => Carbon::now()->addMinutes(2), 'user_id' => 1, 'first_post_id' => 4, 'comment_count' => 1, 'is_sticky' => false, 'last_post_number' => 1],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Text</p></t>', 'number' => 1],
                ['id' => 2, 'discussion_id' => 2, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Text</p></t>', 'number' => 1],
                ['id' => 3, 'discussion_id' => 3, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Text</p></t>', 'number' => 1],
                ['id' => 4, 'discussion_id' => 4, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Text</p></t>', 'number' => 1],
            ],
            Group::class => [
                ['id' => 5, 'name_singular' => 'Group', 'name_plural' => 'Groups', 'color' => 'blue'],
            ],
            'group_user' => [
                ['user_id' => 2, 'group_id' => 5]
            ],
            'group_permission' => [
                ['group_id' => 5, 'permission' => 'discussion.sticky'],
            ],
        ]);
    }

    #[Test]
    #[DataProvider('stickyDataProvider')]
    public function can_sticky_if_allowed(int $actorId, bool $allowed, bool $sticky)
    {
        $response = $this->send(
            $this->request('PATCH', '/api/discussions/1', [
                'authenticatedAs' => $actorId,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'isSticky' => $sticky
                        ]
                    ]
                ]
            ])
        );

        $body = $response->getBody()->getContents();
        $json = json_decode($body, true);

        if ($allowed) {
            $this->assertEquals(200, $response->getStatusCode(), $body);
            $this->assertEquals($sticky, $json['data']['attributes']['isSticky']);
        } else {
            $this->assertEquals(403, $response->getStatusCode(), $body);
        }
    }

    public static function stickyDataProvider(): array
    {
        return [
            [1, true, true],
            [1, true, false],
            [2, true, true],
            [2, true, false],
            [3, false, true],
            [3, false, false],
        ];
    }

    #[Test]
    public function sticky_response_exposes_new_event_post_in_linkage()
    {
        // Discussion 2 starts un-stickied with one comment post (id 2). Stickying
        // creates a `discussionStickied` event post via mergePost(). The PATCH
        // response must surface that new post in the discussion's `posts`
        // relationship linkage so the client can refresh its post stream without
        // a full reload. See flarum/framework#TBD.
        $response = $this->send(
            $this->request('PATCH', '/api/discussions/2', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'isSticky' => true,
                        ],
                    ],
                ],
            ])
        );

        $body = $response->getBody()->getContents();
        $json = json_decode($body, true);

        $this->assertEquals(200, $response->getStatusCode(), $body);

        $linkage = $json['data']['relationships']['posts']['data'] ?? null;
        $this->assertIsArray($linkage, 'PATCH response must include the discussion posts relationship linkage');

        $linkageIds = array_map(fn (array $entry) => $entry['id'], $linkage);

        // The original comment post is still there.
        $this->assertContains('2', $linkageIds, 'Linkage should still contain the original comment post id');

        // And the newly-created event post is too — its id is whatever the next
        // available id is after the fixture posts (1–4). The discussion gained
        // exactly one post; assert the linkage grew by one.
        $this->assertCount(2, $linkageIds, 'Linkage should contain the comment post and the new event post');

        // Identify the new post id (the one that isn't '2') and assert it
        // corresponds to a `discussionStickied` post in the included array.
        $newPostId = array_values(array_diff($linkageIds, ['2']))[0] ?? null;
        $this->assertNotNull($newPostId);

        $stickiedRow = Post::query()->find($newPostId);
        $this->assertNotNull($stickiedRow, 'New post in linkage should exist');
        $this->assertEquals('discussionStickied', $stickiedRow->type);
        $this->assertEquals(2, $stickiedRow->discussion_id);
    }
}
