<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Subscriptions\Tests\integration\api\discussions;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;

class SubscribeTest extends TestCase
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
                ['id' => 1, 'discussion_id' => 1, 'created_at' => Carbon::createFromDate(1975, 5, 21)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>foo bar</p></t>', 'number' => 1],
                ['id' => 2, 'discussion_id' => 2, 'created_at' => Carbon::createFromDate(1975, 5, 21)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>foo bar</p></t>', 'number' => 1],

                ['id' => 33, 'discussion_id' => 33, 'created_at' => Carbon::createFromDate(1975, 5, 21)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>foo bar</p></t>', 'number' => 1],
                ['id' => 34, 'discussion_id' => 33, 'created_at' => Carbon::createFromDate(1975, 5, 21)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>foo bar</p></t>', 'number' => 2],
                ['id' => 35, 'discussion_id' => 33, 'created_at' => Carbon::createFromDate(1975, 5, 21)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>foo bar</p></t>', 'number' => 3],
                ['id' => 36, 'discussion_id' => 33, 'created_at' => Carbon::createFromDate(1975, 5, 21)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>foo bar</p></t>', 'number' => 4],
                ['id' => 37, 'discussion_id' => 33, 'created_at' => Carbon::createFromDate(1975, 5, 21)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>foo bar</p></t>', 'number' => 5],
                ['id' => 38, 'discussion_id' => 33, 'created_at' => Carbon::createFromDate(1975, 5, 21)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>foo bar</p></t>', 'number' => 6],
            ],
            'discussion_user' => [
                ['discussion_id' => 1, 'user_id' => 1, 'last_read_post_number' => 1, 'subscription' => 'follow'],
                ['discussion_id' => 1, 'user_id' => 2, 'last_read_post_number' => 1, 'subscription' => null],
                ['discussion_id' => 2, 'user_id' => 1, 'last_read_post_number' => 1, 'subscription' => 'follow'],

                ['discussion_id' => 33, 'user_id' => 2, 'last_read_post_number' => 1, 'subscription' => 'follow'],
                ['discussion_id' => 33, 'user_id' => 3, 'last_read_post_number' => 1, 'subscription' => 'ignore'],
            ]
        ]);
    }

    /**
     * @test
     * @dataProvider provideStates
     */
    public function can_subscribe_to_a_discussion(int $actorId, int $discussionId, ?string $newState)
    {
        $this->app();

        $response = $this->send(
            $this->request('PATCH', '/api/discussions/'.$discussionId, [
                'authenticatedAs' => $actorId,
                'json' => [
                    'data' => [
                        'type' => 'discussions',
                        'attributes' => [
                            'subscription' => $newState,
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), $response->getBody()->getContents());
        $this->assertEquals($newState, $this->database()->table('discussion_user')->where('discussion_id', $discussionId)->where('user_id', $actorId)->value('subscription'));
    }

    public static function provideStates()
    {
        return [
            'follow' => [2, 1, 'follow'],
            'ignore' => [2, 1, 'ignore'],
            'null' => [2, 1, null],
        ];
    }
}
