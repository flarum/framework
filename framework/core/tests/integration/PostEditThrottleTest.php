<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Http\RequestUtil;
use Flarum\Post\Post;
use Flarum\Post\PostCreationThrottler;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\Attributes\Test;

/**
 * Re-parsing a post's content (resolving @mentions, rendering) is the
 * expensive part of saving it, and it runs on edit as well as create. Creation
 * is flood-controlled; editing re-runs the same work, so it must be caught by
 * the same throttle — otherwise a member repeats a costly edit without limit.
 */
class PostEditThrottleTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            Discussion::class => [
                ['id' => 1, 'title' => __CLASS__, 'created_at' => Carbon::now(), 'user_id' => 2, 'first_post_id' => 1],
            ],
            User::class => [
                $this->normalUser(),
            ],
        ]);
    }

    private function seedPost(?string $createdAt, ?string $editedAt): void
    {
        $this->database()->table('posts')->insert([
            'discussion_id' => 1,
            'user_id' => 2,
            'type' => 'comment',
            'content' => '<t><p>x</p></t>',
            'created_at' => $createdAt,
            'edited_at' => $editedAt,
        ]);
    }

    private function throttleResult(string $routeName): ?bool
    {
        $actor = User::query()->find(2);

        $request = (new ServerRequest())
            ->withAttribute('routeName', $routeName);
        $request = RequestUtil::withActor($request, $actor);

        return $this->app()->getContainer()->make(PostCreationThrottler::class)($request);
    }

    #[Test]
    public function a_recent_edit_throttles_a_further_edit()
    {
        $this->app();
        $this->seedPost(Carbon::now()->subDay()->toDateTimeString(), Carbon::now()->toDateTimeString());

        $this->assertTrue(
            $this->throttleResult('posts.update'),
            'a post edited within the timeout window must throttle a further edit'
        );
    }

    #[Test]
    public function an_old_edit_does_not_throttle()
    {
        $this->app();
        $this->seedPost(Carbon::now()->subDay()->toDateTimeString(), Carbon::now()->subDay()->toDateTimeString());

        $this->assertNull(
            $this->throttleResult('posts.update'),
            'an edit long ago must not throttle'
        );
    }

    #[Test]
    public function posts_update_is_a_throttled_route()
    {
        // With no recent activity the throttler returns null (no opinion), but
        // the route must be one it inspects at all — a regression removing
        // posts.update from the list would make even a recent edit return null.
        $this->app();
        $this->seedPost(Carbon::now()->toDateTimeString(), null);

        $this->assertTrue(
            $this->throttleResult('posts.update'),
            'a recent create must also throttle an edit (shared re-parse budget)'
        );
    }
}
