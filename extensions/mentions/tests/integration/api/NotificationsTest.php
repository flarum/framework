<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\tests\integration\api\NotificationsTest;

use Carbon\Carbon;
use Flarum\Group\Group;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;

class NotificationsTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-mentions');

        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'acme', 'email' => 'acme@machine.local', 'is_email_confirmed' => 1, 'preferences' => json_encode(['flarum-subscriptions.notify_for_all_posts' => true])],
                ['id' => 4, 'username' => 'acme2', 'email' => 'acme2@machine.local', 'is_email_confirmed' => 1],
            ],
            'discussions' => [
                ['id' => 1, 'title' => __CLASS__, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1, 'last_post_number' => 1, 'last_post_id' => 1],
                ['id' => 2, 'title' => __CLASS__, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 2, 'comment_count' => 1, 'last_post_number' => 1, 'last_post_id' => 2],

                ['id' => 33, 'title' => __CLASS__, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 33, 'comment_count' => 6, 'last_post_number' => 6, 'last_post_id' => 38],
            ],
            'posts' => [
                ['id' => 1, 'discussion_id' => 1, 'created_at' => Carbon::createFromDate(1975, 5, 21)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>foo bar</p></t>', 'number' => 1],
                ['id' => 2, 'discussion_id' => 2, 'created_at' => Carbon::createFromDate(1975, 5, 21)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>foo bar</p></t>', 'number' => 1],

                ['id' => 33, 'discussion_id' => 33, 'created_at' => Carbon::createFromDate(1975, 5, 21)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>foo bar</p></t>', 'number' => 1],
                ['id' => 34, 'discussion_id' => 33, 'created_at' => Carbon::createFromDate(1975, 5, 21)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>foo bar</p></t>', 'number' => 2],
                ['id' => 35, 'discussion_id' => 33, 'created_at' => Carbon::createFromDate(1975, 5, 21)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>foo bar</p></t>', 'number' => 3],
                ['id' => 36, 'discussion_id' => 33, 'created_at' => Carbon::createFromDate(1975, 5, 21)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>foo bar</p></t>', 'number' => 4],
                ['id' => 37, 'discussion_id' => 33, 'created_at' => Carbon::createFromDate(1975, 5, 21)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>foo bar</p></t>', 'number' => 5],
                ['id' => 38, 'discussion_id' => 33, 'created_at' => Carbon::createFromDate(1975, 5, 21)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>foo bar</p></t>', 'number' => 6],
            ],
        ]);
    }

    /** @test */
    public function approving_reply_sends_mention_notification()
    {
        $this->extensions = ['flarum-flags', 'flarum-approval', 'flarum-mentions'];

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
                        'attributes' => [
                            'content' => 'reply with predetermined content for automated testing - too-obscure',
                        ],
                        'relationships' => [
                            'discussion' => ['data' => ['id' => 1]],
                        ],
                    ]
                ]
            ])
        );

        $this->assertEquals(0, $mainUser->getUnreadNotificationCount());

        $json = json_decode($response->getBody()->getContents(), true);

        $this->send(
            $this->request('PATCH', '/api/posts'.$json['data']['id'], [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'isApproved' => 1,
                        ]
                    ]
                ]
            ])
        );
    }
}
