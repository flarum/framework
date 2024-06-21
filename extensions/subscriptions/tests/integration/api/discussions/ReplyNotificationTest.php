<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Subscriptions\tests\integration\api\discussions;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Extend\ModelVisibility;
use Flarum\Group\Group;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;

class ReplyNotificationTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-subscriptions');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'acme', 'email' => 'acme@machine.local', 'is_email_confirmed' => 1, 'preferences' => json_encode(['flarum-subscriptions.notify_for_all_posts' => true])],
                ['id' => 4, 'username' => 'acme2', 'email' => 'acme2@machine.local', 'is_email_confirmed' => 1],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => __CLASS__, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1, 'last_post_number' => 1, 'last_post_id' => 1],
                ['id' => 2, 'title' => __CLASS__, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 2, 'comment_count' => 1, 'last_post_number' => 1, 'last_post_id' => 2],

                ['id' => 33, 'title' => __CLASS__, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 33, 'comment_count' => 6, 'last_post_number' => 6, 'last_post_id' => 38],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'created_at' => Carbon::createFromDate(1975, 5, 21)->addMinutes(1)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>foo bar</p></t>', 'number' => 1],
                ['id' => 2, 'discussion_id' => 2, 'created_at' => Carbon::createFromDate(1975, 5, 21)->addMinutes(2)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>foo bar</p></t>', 'number' => 1],

                ['id' => 33, 'discussion_id' => 33, 'created_at' => Carbon::createFromDate(1975, 5, 21)->addMinutes(3)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>foo bar</p></t>', 'number' => 1],
                ['id' => 34, 'discussion_id' => 33, 'created_at' => Carbon::createFromDate(1975, 5, 21)->addMinutes(4)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>foo bar</p></t>', 'number' => 2],
                ['id' => 35, 'discussion_id' => 33, 'created_at' => Carbon::createFromDate(1975, 5, 21)->addMinutes(5)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>foo bar</p></t>', 'number' => 3],
                ['id' => 36, 'discussion_id' => 33, 'created_at' => Carbon::createFromDate(1975, 5, 21)->addMinutes(6)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>foo bar</p></t>', 'number' => 4],
                ['id' => 37, 'discussion_id' => 33, 'created_at' => Carbon::createFromDate(1975, 5, 21)->addMinutes(7)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>foo bar</p></t>', 'number' => 5],
                ['id' => 38, 'discussion_id' => 33, 'created_at' => Carbon::createFromDate(1975, 5, 21)->addMinutes(8)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>foo bar</p></t>', 'number' => 6],
            ],
            'discussion_user' => [
                ['discussion_id' => 1, 'user_id' => 1, 'last_read_post_number' => 1, 'subscription' => 'follow'],
                ['discussion_id' => 1, 'user_id' => 2, 'last_read_post_number' => 1, 'subscription' => 'follow'],
                ['discussion_id' => 2, 'user_id' => 1, 'last_read_post_number' => 1, 'subscription' => 'follow'],

                ['discussion_id' => 33, 'user_id' => 2, 'last_read_post_number' => 1, 'subscription' => 'follow'],
                ['discussion_id' => 33, 'user_id' => 3, 'last_read_post_number' => 1, 'subscription' => 'follow'],
            ]
        ]);
    }

    /**
     * @dataProvider replyingSendsNotificationsDataProvider
     * @test
     */
    public function replying_to_a_discussion_with_comment_post_as_last_post_sends_reply_notification(int $userId, int $discussionId, int $newNotificationCount)
    {
        $this->app();

        /** @var User $mainUser */
        $mainUser = User::query()->find($userId);

        $this->assertEquals(0, $mainUser->getUnreadNotificationCount());

        for ($i = 0; $i < 5; $i++) {
            $this->send(
                $this->request('POST', '/api/posts', [
                    'authenticatedAs' => 4,
                    'json' => [
                        'data' => [
                            'attributes' => [
                                'content' => 'reply with predetermined content for automated testing - too-obscure',
                            ],
                            'relationships' => [
                                'discussion' => ['data' => ['id' => $discussionId]],
                            ],
                        ],
                    ],
                ])->withAttribute('bypassThrottling', true)
            );
        }

        $this->assertEquals($newNotificationCount, $mainUser->getUnreadNotificationCount());
    }

    public function replyingSendsNotificationsDataProvider(): array
    {
        return [
            'admin receives a notification when another replies to a discussion they are following and caught up to' => [1, 1, 1],
            'user receives a notification when another replies to a discussion they are following and caught up to' => [2, 1, 1],

            'user receives notification for every new post to a discussion they are following when preference is on' => [3, 33, 5],
        ];
    }

    /** @test */
    public function replying_to_a_discussion_with_event_post_as_last_post_sends_reply_notification()
    {
        $this->app();

        /** @var User $mainUser */
        $mainUser = User::query()->find(1);

        // Rename the discussion to trigger an event post.
        $this->send(
            $this->request('POST', '/api/discussions/2', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'type' => 'discussions',
                        'attributes' => [
                            'title' => 'ACME',
                        ],
                    ],
                ],
            ])
        );

        // Mark as read
        $this->send(
            $this->request('POST', '/api/discussions/2', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'type' => 'discussions',
                        'attributes' => [
                            'lastReadPostNumber' => 2,
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(0, $mainUser->getUnreadNotificationCount());

        $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 2,
                'json' => [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'content' => 'reply with predetermined content for automated testing - too-obscure',
                        ],
                        'relationships' => [
                            'discussion' => ['data' => ['id' => 2]],
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(1, $mainUser->getUnreadNotificationCount());
    }

    /**
     * @dataProvider deleteLastPostsProvider
     * @test
     */
    public function deleting_last_posts_then_posting_new_one_sends_reply_notification(array $postIds)
    {
        $this->prepareDatabase([
            Discussion::class => [
                ['id' => 3, 'title' => __CLASS__, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 5, 'last_post_number' => 5, 'last_post_id' => 10],
            ],
            Post::class => [
                ['id' => 5, 'discussion_id' => 3, 'created_at' => Carbon::createFromDate(1975, 5, 21)->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>foo bar</p></t>', 'number' => 1],
                ['id' => 6, 'discussion_id' => 3, 'created_at' => Carbon::createFromDate(1975, 5, 21)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>foo bar</p></t>', 'number' => 2],
                ['id' => 7, 'discussion_id' => 3, 'created_at' => Carbon::createFromDate(1975, 5, 21)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>foo bar</p></t>', 'number' => 3],
                ['id' => 8, 'discussion_id' => 3, 'created_at' => Carbon::createFromDate(1975, 5, 21)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>foo bar</p></t>', 'number' => 4],
                ['id' => 9, 'discussion_id' => 3, 'created_at' => Carbon::createFromDate(1975, 5, 21)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>foo bar</p></t>', 'number' => 5],
                ['id' => 10, 'discussion_id' => 3, 'created_at' => Carbon::createFromDate(1975, 5, 21)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>foo bar</p></t>', 'number' => 6],
            ],
            'discussion_user' => [
                ['discussion_id' => 3, 'user_id' => 2, 'last_read_post_number' => 6, 'subscription' => 'follow'],
            ]
        ]);

        // Delete the last 3 posts.
        foreach ($postIds as $postId) {
            $this->send(
                $this->request('DELETE', '/api/posts/'.$postId, ['authenticatedAs' => 1])
            );
        }

        /** @var User $mainUser */
        $mainUser = User::query()->find(2);

        $this->assertEquals(0, $mainUser->getUnreadNotificationCount());

        // Reply as another user
        $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 3,
                'json' => [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'content' => 'reply with predetermined content for automated testing - too-obscure',
                        ],
                        'relationships' => [
                            'discussion' => ['data' => ['id' => 3]],
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(1, $mainUser->getUnreadNotificationCount());
    }

    public function deleteLastPostsProvider(): array
    {
        return [
            [[10, 9, 8]],
            [[8, 9, 10]]
        ];
    }

    /** @test */
    public function approving_reply_sends_reply_notification()
    {
        // Flags was only specified because it is required for approval.
        $this->extensions = ['flarum-flags', 'flarum-approval', 'flarum-subscriptions'];

        $this->app();

        $this->database()
            ->table('group_permission')
            ->where('group_id', Group::MEMBER_ID)
            ->where('permission', 'discussion.replyWithoutApproval')
            ->delete();

        /** @var User $mainUser */
        $mainUser = User::query()->find(2);

        $this->assertEquals(0, $mainUser->getUnreadNotificationCount());

        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 4,
                'json' => [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'content' => 'reply with predetermined content for automated testing - too-obscure',
                        ],
                        'relationships' => [
                            'discussion' => ['data' => ['id' => 1]],
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(0, $mainUser->getUnreadNotificationCount());

        $json = json_decode($response->getBody()->getContents(), true);

        // Approve the previous post
        $this->send(
            $this->request('PATCH', '/api/posts/'.$json['data']['id'], [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'isApproved' => 1,
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(1, $mainUser->getUnreadNotificationCount());
    }

    /** @test */
    public function replying_to_a_discussion_with_a_restricted_post_only_sends_notifications_to_allowed_users()
    {
        // Add visibility scoper to only allow admin
        // to see expected new post with content containing 'restricted-test-post'.
        $this->extend(
            (new ModelVisibility(Post::class))
                ->scope(function (User $actor, $query) {
                    if (! $actor->isAdmin()) {
                        $query->where('content', 'not like', '%restricted-test-post%');
                    }
                })
        );

        $this->app();

        /** @var User $allowedUser */
        $allowedUser = User::query()->find(1);
        $normalUser = User::query()->find(2);

        $this->assertEquals(0, $allowedUser->getUnreadNotificationCount());
        $this->assertEquals(0, $normalUser->getUnreadNotificationCount());

        $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 3,
                'json' => [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'content' => 'restricted-test-post',
                        ],
                        'relationships' => [
                            'discussion' => ['data' => ['id' => 1]],
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(1, $allowedUser->getUnreadNotificationCount());
        $this->assertEquals(0, $normalUser->getUnreadNotificationCount());
    }
}
