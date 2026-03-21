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
use PHPUnit\Framework\Attributes\Test;

class FollowAfterReplyTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-subscriptions');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'acme_follow', 'email' => 'acme@machine.local', 'is_email_confirmed' => 1, 'preferences' => json_encode(['followAfterReply' => true])],
                ['id' => 4, 'username' => 'acme_no_follow', 'email' => 'acme2@machine.local', 'is_email_confirmed' => 1, 'preferences' => json_encode(['followAfterReply' => false])],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => __CLASS__, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1, 'last_post_number' => 1, 'last_post_id' => 1],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'created_at' => Carbon::createFromDate(1975, 5, 21)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>foo bar</p></t>', 'number' => 1],
            ],
        ]);
    }

    #[Test]
    public function user_with_preference_true_follows_after_replying_to_discussion()
    {
        $this->app();

        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 3,
                'json' => [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'content' => 'reply with predetermined content for automated testing'
                        ],
                        'relationships' => [
                            'discussion' => ['data' => ['id' => 1]],
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(201, $response->getStatusCode());

        $this->assertEquals('follow', $this->database()->table('discussion_user')->where('discussion_id', 1)->where('user_id', 3)->value('subscription'));
    }

    #[Test]
    public function user_with_preference_false_does_not_follow_after_replying_to_discussion()
    {
        $this->app();

        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 4,
                'json' => [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'content' => 'reply with predetermined content for automated testing'
                        ],
                        'relationships' => [
                            'discussion' => ['data' => ['id' => 1]],
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(201, $response->getStatusCode());

        $this->assertNull($this->database()->table('discussion_user')->where('discussion_id', 1)->where('user_id', 4)->value('subscription'));
    }
}
