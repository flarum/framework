<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\discussions;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Extend;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\Test;

/**
 * The `posts` ID linkage on the show-discussion endpoint drives the post
 * stream (scrubber positions, near-navigation, range loading), so its
 * visibility semantics must exactly match what the actor may see. These pin
 * the semantics so the ID query can be optimised without changing them.
 */
class PostIdsVisibilityTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            Discussion::class => [
                ['id' => 10, 'title' => 'Posts of all kinds', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 100, 'comment_count' => 5, 'is_private' => 0],
            ],
            Post::class => [
                ['id' => 100, 'discussion_id' => 10, 'number' => 1, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>normal one</p></t>'],
                ['id' => 101, 'discussion_id' => 10, 'number' => 2, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>hidden, authored by user 2</p></t>', 'hidden_at' => Carbon::now()->toDateTimeString()],
                ['id' => 102, 'discussion_id' => 10, 'number' => 3, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 3, 'type' => 'comment', 'content' => '<t><p>hidden, authored by user 3</p></t>', 'hidden_at' => Carbon::now()->toDateTimeString()],
                ['id' => 103, 'discussion_id' => 10, 'number' => 4, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>private</p></t>', 'is_private' => 1],
                ['id' => 104, 'discussion_id' => 10, 'number' => 5, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 3, 'type' => 'comment', 'content' => '<t><p>normal two</p></t>'],
            ],
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'acquaintance', 'email' => 'acquaintance@machine.local', 'is_email_confirmed' => 1],
            ],
        ]);
    }

    protected function tearDown(): void
    {
        // Visibility scopers live in static model state, so ones registered by
        // this test's extenders would leak into every later test in the
        // process. The next app boot re-registers core's scopers.
        (function () {
            self::$visibilityScopers = [];
        })->bindTo(null, Post::class)();

        parent::tearDown();
    }

    private function postIds(?int $actorId = null): array
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions/10', $actorId ? ['authenticatedAs' => $actorId] : [])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $json = json_decode($response->getBody()->getContents(), true);

        return array_map('intval', array_column(Arr::get($json, 'data.relationships.posts.data', []), 'id'));
    }

    #[Test]
    public function guest_sees_only_public_visible_post_ids()
    {
        $this->assertSame([100, 104], $this->postIds());
    }

    #[Test]
    public function author_sees_own_hidden_post_id_but_not_others()
    {
        $this->assertSame([100, 101, 104], $this->postIds(2));
    }

    #[Test]
    public function other_user_sees_own_hidden_post_id_but_not_others()
    {
        $this->assertSame([100, 102, 104], $this->postIds(3));
    }

    #[Test]
    public function admin_sees_hidden_post_ids_but_not_private_without_a_grant()
    {
        // No viewPrivate scoper grants access in core, so the private post
        // stays out even for admins; hidden posts are visible via the
        // discussion.hidePosts permission.
        $this->assertSame([100, 101, 102, 104], $this->postIds(1));
    }

    #[Test]
    public function private_post_id_appears_when_a_view_private_scoper_grants_it()
    {
        $this->extend(
            (new Extend\ModelVisibility(Post::class))
                ->scope(AllowAllPrivatePosts::class, 'viewPrivate')
        );

        $this->assertSame([100, 103, 104], $this->postIds());
    }

    #[Test]
    public function extension_view_scopers_still_restrict_post_ids()
    {
        // Approval-style: an extension scoping the `view` ability must keep
        // applying to the ID query (approval hides unapproved posts this way).
        $this->extend(
            (new Extend\ModelVisibility(Post::class))
                ->scope(HideSecondCommentScoper::class, 'view')
        );

        $this->assertSame([100], $this->postIds());
    }
}

class AllowAllPrivatePosts
{
    public function __invoke(User $actor, Builder $query): void
    {
        $query->orWhereNotNull('posts.id');
    }
}

class HideSecondCommentScoper
{
    public function __invoke(User $actor, Builder $query): void
    {
        $query->where('posts.id', '!=', 104);
    }
}
