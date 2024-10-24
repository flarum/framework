<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\policy;

use Carbon\Carbon;
use Flarum\Api\JsonApi;
use Flarum\Api\Resource\PostResource;
use Flarum\Discussion\Discussion;
use Flarum\Foundation\DispatchEventsTrait;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class DiscussionPolicyTest extends TestCase
{
    use RetrievesAuthorizedUsers;
    use DispatchEventsTrait;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            Discussion::class => [
                ['id' => 1, 'title' => 'Editable discussion', 'created_at' => Carbon::parse('2021-11-01 13:00:00')->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 2, 'is_private' => 0, 'last_post_number' => 1, 'participant_count' => 1],
                ['id' => 2, 'title' => 'Editable discussion', 'created_at' => Carbon::parse('2021-11-01 13:00:00')->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 2, 'comment_count' => 2, 'is_private' => 0, 'last_post_number' => 2, 'participant_count' => 2],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'number' => 1, 'created_at' => Carbon::parse('2021-11-01 13:00:00')->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t></t>'],
                ['id' => 2, 'discussion_id' => 2, 'number' => 1, 'created_at' => Carbon::parse('2021-11-01 13:00:03')->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t></t>'],
                ['id' => 3, 'discussion_id' => 2, 'number' => 2, 'created_at' => Carbon::parse('2021-11-01 13:00:03')->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t></t>'],
            ],
            User::class => [
                $this->normalUser(),
            ]
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Carbon::setTestNow();
    }

    #[Test]
    public function rename_indefinitely()
    {
        $this->setting('allow_renaming', '-1');
        $this->app();

        $user = User::findOrFail(2);
        $discussion = Discussion::findOrFail(1);

        // Date close to "now"
        Carbon::setTestNow('2021-11-01 13:00:05');

        $this->assertTrue($user->can('rename', $discussion));

        // Date further into the future
        Carbon::setTestNow('2025-01-01 13:00:00');

        $this->assertTrue($user->can('rename', $discussion));
    }

    #[Test]
    public function rename_until_reply()
    {
        $this->setting('allow_renaming', 'reply');
        $this->app();

        $user = User::findOrFail(2);
        $discussion = Discussion::findOrFail(1);
        $discussionWithReply = Discussion::findOrFail(2);

        // Date close to "now"
        Carbon::setTestNow('2021-11-01 13:00:05');

        $this->assertTrue($user->can('rename', $discussion));
        $this->assertFalse($user->can('rename', $discussionWithReply));

        /** @var JsonApi $api */
        $api = $this->app()->getContainer()->make(JsonApi::class);

        $api
            ->forResource(PostResource::class)
            ->forEndpoint('create')
            ->process(
                body: [
                    'data' => [
                        'attributes' => [
                            'content' => 'test'
                        ],
                        'relationships' => [
                            'discussion' => [
                                'data' => [
                                    'type' => 'discussions',
                                    'id' => '1'
                                ],
                            ],
                        ],
                    ],
                ],
                options: ['actor' => User::findOrFail(1)]
            );

        // Date further into the future
        Carbon::setTestNow('2025-01-01 13:00:00');

        $this->assertFalse($user->can('rename', $discussion->fresh()));
        $this->assertFalse($user->can('rename', $discussionWithReply));
    }

    #[Test]
    public function rename_10_minutes()
    {
        $this->setting('allow_renaming', '10');
        $this->app();

        $user = User::findOrFail(2);
        $discussion = Discussion::findOrFail(1);

        // Date close to "now"
        Carbon::setTestNow('2021-11-01 13:00:05');

        $this->assertTrue($user->can('rename', $discussion));

        // Date further into the future
        Carbon::setTestNow('2025-01-01 13:00:00');

        $this->assertFalse($user->can('rename', $discussion));
    }
}
