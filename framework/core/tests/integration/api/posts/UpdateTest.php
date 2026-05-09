<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\posts;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Post\CommentPost;
use Flarum\Post\Event\Revised;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class UpdateTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            Discussion::class => [
                ['id' => 1, 'title' => 'Discussion with post', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 1, 'is_private' => 0],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => null],
            ],
            User::class => [
                $this->normalUser(),
            ],
        ]);
    }

    #[Test]
    public function revising_a_post_with_null_content_does_not_throw(): void
    {
        // Regression test for #4606. Posts can have NULL content (legacy
        // imports, extension-created posts that don't set content). When
        // such a post is edited, CommentPost::revise() raises a Revised
        // event with $oldContent = null, but Revised's constructor types
        // $oldContent as non-nullable string — TypeError.
        $this->app();

        /** @var CommentPost $post */
        $post = CommentPost::find(1);
        $this->assertNull($post->content);

        $actor = User::find(2);

        $post->revise('<t><p>new content</p></t>', $actor);

        $post->save();

        $this->assertSame('<t><p>new content</p></t>', $post->refresh()->content);
    }

    #[Test]
    public function revising_a_post_with_null_content_raises_revised_event(): void
    {
        // Listeners that subscribed before the fix should still receive a
        // Revised event when the post's old content was null. The event is
        // the only way to know an edit happened, so dropping it would
        // silently break audit/log/index extensions.
        $this->app();

        $post = CommentPost::find(1);
        $actor = User::find(2);
        $post->revise('<t><p>new content</p></t>', $actor);

        $events = $post->releaseEvents();
        $revised = array_filter($events, fn ($e) => $e instanceof Revised);

        $this->assertCount(1, $revised, 'Revised event was not raised.');
        $this->assertSame('', array_values($revised)[0]->oldContent, 'oldContent should be coalesced to empty string when the original was null.');
    }
}
