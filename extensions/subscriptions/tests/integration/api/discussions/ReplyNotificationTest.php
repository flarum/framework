<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Subscriptions\tests\integration\api\discussions;

use Carbon\Carbon;
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
            'users' => [
                $this->normalUser(),
            ],
            'discussions' => [
                ['id' => 1, 'title' => __CLASS__, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1, 'last_post_number' => 1, 'last_post_id' => 1],
                ['id' => 2, 'title' => __CLASS__, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 2, 'comment_count' => 1, 'last_post_number' => 1, 'last_post_id' => 2],
            ],
            'posts' => [
                ['id' => 1, 'discussion_id' => 1, 'created_at' => Carbon::createFromDate(1975, 5, 21)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>foo bar</p></t>', 'number' => 1],
                ['id' => 2, 'discussion_id' => 2, 'created_at' => Carbon::createFromDate(1975, 5, 21)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>foo bar</p></t>', 'number' => 1],
            ],
            'discussion_user' => [
                ['discussion_id' => 1, 'user_id' => 1, 'last_read_post_number' => 1, 'subscription' => 'follow'],
                ['discussion_id' => 2, 'user_id' => 1, 'last_read_post_number' => 1, 'subscription' => 'follow'],
            ]
        ]);
    }

    /** @test */
    public function replying_to_a_discussion_with_comment_post_as_last_post_sends_reply_notification()
    {
        $this->app();

        /** @var User $mainUser */
        $mainUser = User::query()->find(1);

        $this->assertEquals(0, $this->getUnreadNotificationCount($mainUser));

        $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 2,
                'json' => [
                    'data' => [
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

        $this->assertEquals(1, $this->getUnreadNotificationCount($mainUser));
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
                        'attributes' => [
                            'lastReadPostNumber' => 2,
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(0, $this->getUnreadNotificationCount($mainUser));

        $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 2,
                'json' => [
                    'data' => [
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

        $this->assertEquals(1, $this->getUnreadNotificationCount($mainUser));
    }

    /** @todo change after core no longer statically caches unread notification in the User class */
    protected function getUnreadNotificationCount(User $user)
    {
        return $user->notifications()
            ->where('type', 'newPost')
            ->whereNull('read_at')
            ->where('is_deleted', false)
            ->whereSubjectVisibleTo($user)
            ->count();
    }
}
