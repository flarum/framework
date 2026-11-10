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
use Flarum\Extend;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\Test;

/**
 * Characterizes ScopePostVisibility's discussion-level branches — the
 * discussion must be visible, and hidden posts show for actors granted the
 * hidePosts ability on the post's discussion — so the SQL shape (per-row
 * EXISTS vs materialized IN-subquery) can change without the semantics
 * moving. The post-local branches (own hidden posts, private posts) are
 * covered by ShowTest and PostIdsVisibilityTest.
 */
class PostVisibilityTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            Discussion::class => [
                ['id' => 1, 'title' => 'Public', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 10, 'comment_count' => 2, 'is_private' => 0],
                ['id' => 2, 'title' => 'Private', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 20, 'comment_count' => 1, 'is_private' => 1],
                ['id' => 3, 'title' => 'Soft-deleted', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 30, 'comment_count' => 1, 'is_private' => 0, 'hidden_at' => Carbon::now()->toDateTimeString()],
            ],
            Post::class => [
                ['id' => 10, 'discussion_id' => 1, 'number' => 1, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>public post</p></t>'],
                ['id' => 11, 'discussion_id' => 1, 'number' => 2, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>hidden post</p></t>', 'hidden_at' => Carbon::now()->toDateTimeString()],
                ['id' => 20, 'discussion_id' => 2, 'number' => 1, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>post in private discussion</p></t>'],
                ['id' => 30, 'discussion_id' => 3, 'number' => 1, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>post in soft-deleted discussion</p></t>'],
            ],
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'onlooker', 'email' => 'onlooker@machine.local', 'is_email_confirmed' => 1],
            ],
        ]);
    }

    protected function tearDown(): void
    {
        // Scopers registered through test extenders live in static model
        // state and would leak into later tests in this process.
        (function () {
            self::$visibilityScopers = [];
        })->bindTo(null, Discussion::class)();

        parent::tearDown();
    }

    private function visiblePostIds(?int $actorId = null): array
    {
        $response = $this->send(
            $this->request('GET', '/api/posts', $actorId ? ['authenticatedAs' => $actorId] : [])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $json = json_decode($response->getBody()->getContents(), true);

        $ids = array_map('intval', array_column(Arr::get($json, 'data', []), 'id'));
        sort($ids);

        return $ids;
    }

    #[Test]
    public function posts_of_invisible_discussions_are_excluded_for_guests()
    {
        // Private (2) and soft-deleted (3) discussions are invisible, so their
        // posts never appear; the hidden post (11) is excluded post-locally.
        $this->assertSame([10], $this->visiblePostIds());
    }

    #[Test]
    public function discussion_author_sees_posts_of_their_soft_deleted_discussion()
    {
        // ScopeDiscussionVisibility lets authors see their own hidden
        // discussions, and post visibility must follow it.
        $this->assertSame([10, 11, 30], $this->visiblePostIds(2));
    }

    #[Test]
    public function unrelated_user_sees_only_public_discussion_posts()
    {
        $this->assertSame([10], $this->visiblePostIds(3));
    }

    #[Test]
    public function hidden_posts_appear_when_a_scoper_grants_hide_posts_on_the_discussion()
    {
        // Grants the hidePosts ability on discussion 1 only — like a per-tag
        // moderator. User 3 gains the hidden post there, and nothing else.
        $this->extend(
            (new Extend\ModelVisibility(Discussion::class))
                ->scope(GrantHidePostsOnDiscussionOne::class, 'hidePosts')
        );

        $this->assertSame([10, 11], $this->visiblePostIds(3));
    }

    #[Test]
    public function without_any_hide_posts_scoper_no_discussion_grants_hidden_posts()
    {
        // The ability subquery must stay EMPTY when nothing scopes it — a
        // seeded 1=0 guards against an unconstrained "all discussions" set.
        $this->assertSame([10], $this->visiblePostIds(3));
    }
}

class GrantHidePostsOnDiscussionOne
{
    public function __invoke(User $actor, Builder $query): void
    {
        $query->orWhere('discussions.id', 1);
    }
}
