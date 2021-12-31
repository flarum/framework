<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\policy;

use Carbon\Carbon;
use Flarum\Bus\Dispatcher;
use Flarum\Discussion\Discussion;
use Flarum\Foundation\DispatchEventsTrait;
use Flarum\Post\Command\PostReply;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;

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
            'discussions' => [
                ['id' => 1, 'title' => 'Editable discussion', 'created_at' => Carbon::parse('2021-11-01 13:00:00')->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 2, 'is_private' => 0, 'last_post_number' => 1, 'post_number_index' => 1, 'participant_count' => 1],
                ['id' => 2, 'title' => 'Editable discussion', 'created_at' => Carbon::parse('2021-11-01 13:00:00')->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 2, 'comment_count' => 2, 'is_private' => 0, 'last_post_number' => 2, 'participant_count' => 2],
            ],
            'posts' => [
                ['id' => 1, 'discussion_id' => 1, 'number' => 1, 'created_at' => Carbon::parse('2021-11-01 13:00:00')->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t></t>'],
                ['id' => 2, 'discussion_id' => 2, 'number' => 1, 'created_at' => Carbon::parse('2021-11-01 13:00:03')->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t></t>'],
                ['id' => 3, 'discussion_id' => 2, 'number' => 2, 'created_at' => Carbon::parse('2021-11-01 13:00:03')->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t></t>'],
            ],
            'users' => [
                $this->normalUser(),
            ]
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Carbon::setTestNow();
    }

    /**
     * @test
     */
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

    /**
     * @test
     */
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

        $this->app()->getContainer()->make(Dispatcher::class)->dispatch(
            new PostReply(1, User::findOrFail(1), ['attributes' => ['content' => 'test']], null)
        );

        // Date further into the future
        Carbon::setTestNow('2025-01-01 13:00:00');

        $this->assertFalse($user->can('rename', $discussion->fresh()));
        $this->assertFalse($user->can('rename', $discussionWithReply));
    }

    /**
     * @test
     */
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
