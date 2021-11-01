<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\post;

use Carbon\Carbon;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;

class PostPolicyTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            'discussions' => [
                ['id' => 1, 'title' => 'Editable discussion', 'created_at' => Carbon::parse('2021-11-01 13:00:00')->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 2, 'is_private' => 0, 'last_post_number' => 2],
            ],
            'posts' => [
                ['id' => 1, 'discussion_id' => 1, 'number' => 1, 'created_at' => Carbon::parse('2021-11-01 13:00:00')->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t></t>'],
                ['id' => 2, 'discussion_id' => 1, 'number' => 2, 'created_at' => Carbon::parse('2021-11-01 13:00:03')->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t></t>'],
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
    public function edit_indefinitely()
    {
        $this->setting('allow_post_editing', '-1');
        $this->app();

        // Date close to "now"
        Carbon::setTestNow('2021-11-01 13:00:05');

        $this->assertTrue(User::findOrFail(2)->can('edit', Post::findOrFail(1)));
        $this->assertTrue(User::findOrFail(2)->can('edit', Post::findOrFail(2)));

        // Date further into the future
        Carbon::setTestNow('2025-01-01 13:00:00');

        $this->assertTrue(User::findOrFail(2)->can('edit', Post::findOrFail(1)));
        $this->assertTrue(User::findOrFail(2)->can('edit', Post::findOrFail(2)));
    }

    /**
     * @test
     */
    public function edit_until_reply()
    {
        $this->setting('allow_post_editing', 'reply');
        $this->app();

        // Date close to "now"
        Carbon::setTestNow('2021-11-01 13:00:05');

        $this->assertFalse(User::findOrFail(2)->can('edit', Post::findOrFail(1)));
        $this->assertTrue(User::findOrFail(2)->can('edit', Post::findOrFail(2)));

        // Date further into the future
        Carbon::setTestNow('2025-01-01 13:00:00');

        $this->assertFalse(User::findOrFail(2)->can('edit', Post::findOrFail(1)));
        $this->assertTrue(User::findOrFail(2)->can('edit', Post::findOrFail(2)));
    }

    /**
     * @test
     */
    public function edit_10_minutes()
    {
        $this->setting('allow_post_editing', '10');
        $this->app();

        // Date close to "now"
        Carbon::setTestNow('2021-11-01 13:00:05');

        $this->assertTrue(User::findOrFail(2)->can('edit', Post::findOrFail(1)));
        $this->assertTrue(User::findOrFail(2)->can('edit', Post::findOrFail(2)));

        // Date further into the future
        Carbon::setTestNow('2025-01-01 13:00:00');

        $this->assertFalse(User::findOrFail(2)->can('edit', Post::findOrFail(1)));
        $this->assertFalse(User::findOrFail(2)->can('edit', Post::findOrFail(2)));
    }
}
