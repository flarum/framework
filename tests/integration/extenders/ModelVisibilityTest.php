<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Extend;
use Flarum\Post\CommentPost;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

class ModelVisibilityTest extends TestCase
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
                ['id' => 1, 'title' => 'Empty discussion', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => null, 'comment_count' => 0, 'is_private' => 0],
                ['id' => 2, 'title' => 'Discussion with post', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 1, 'is_private' => 0],
                ['id' => 3, 'title' => 'Private discussion', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'first_post_id' => 2, 'comment_count' => 1, 'is_private' => 1],
            ],
            'posts' => [
                ['id' => 1, 'discussion_id' => 2, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>a normal reply - too-obscure</p></t>'],
                ['id' => 2, 'discussion_id' => 3, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>private!</p></t>'],
            ],
            'users' => [
                $this->normalUser(),
            ]
        ]);
    }

    /**
     * @test
     */
    public function when_allowed_guests_can_see_hidden_posts()
    {
        $this->extend(
            (new Extend\ModelVisibility(CommentPost::class))
                ->scope(function (User $user, Builder $query) {
                    $query->whereRaw('1=1');
                }, 'hidePosts')
        );

        $response = $this->send(
            $this->request('GET', '/api/discussions/2')
        );

        $json = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals(1, Arr::get($json, 'data.relationships.posts.data.0.id'));
    }

    /**
     * @test
     */
    public function user_can_see_posts_by_default()
    {
        $this->app();

        $actor = User::find(2);

        $visiblePosts = CommentPost::query()->whereVisibleTo($actor)->get();

        $this->assertCount(1, $visiblePosts);
    }

    /**
     * @test
     */
    public function custom_visibility_scoper_can_stop_user_from_seeing_posts()
    {
        $this->extend(
            (new Extend\ModelVisibility(CommentPost::class))
                ->scope(function (User $user, Builder $query) {
                    $query->whereRaw('1=0');
                }, 'view')
        );

        $this->app();

        $actor = User::find(2);

        $visiblePosts = CommentPost::query()->whereVisibleTo($actor)->get();

        $this->assertCount(0, $visiblePosts);
    }

    /**
     * @test
     */
    public function custom_visibility_scoper_applies_if_added_to_parent_class()
    {
        $this->extend(
            (new Extend\ModelVisibility(Post::class))
                ->scope(function (User $user, Builder $query) {
                    $query->whereRaw('1=0');
                }, 'view')
        );

        $this->app();

        $actor = User::find(2);

        $visiblePosts = CommentPost::query()->whereVisibleTo($actor)->get();

        $this->assertCount(0, $visiblePosts);
    }

    /**
     * @test
     */
    public function custom_visibility_scoper_for_class_applied_after_scopers_for_parent_class()
    {
        $this->extend(
            (new Extend\ModelVisibility(CommentPost::class))
                ->scope(function (User $user, Builder $query) {
                    $query->orWhereRaw('1=1');
                }, 'view'),
            (new Extend\ModelVisibility(Post::class))
                ->scope(function (User $user, Builder $query) {
                    $query->whereRaw('1=0');
                }, 'view')
        );

        $this->app();

        $actor = User::find(2);

        $visiblePosts = CommentPost::query()->whereVisibleTo($actor)->get();

        $this->assertCount(2, $visiblePosts);
    }

    /**
     * @test
     */
    public function custom_scoper_works_for_abilities_other_than_view()
    {
        $this->extend(
            (new Extend\ModelVisibility(Discussion::class))
                ->scope(function (User $user, Builder $query) {
                    $query->whereRaw('1=1');
                }, 'viewPrivate'),
            (new Extend\ModelVisibility(Post::class))
                ->scope(function (User $user, Builder $query) {
                    $query->whereRaw('1=1');
                }, 'viewPrivate')
        );

        $this->app();

        $actor = User::find(2);

        $visiblePosts = CommentPost::query()->whereVisibleTo($actor)->get();

        $this->assertCount(2, $visiblePosts);
    }

    /**
     * @test
     */
    public function universal_scoper_works()
    {
        $this->extend(
            (new Extend\ModelVisibility(Discussion::class))
                ->scopeAll(function (User $user, Builder $query, string $ability) {
                    if ($ability == 'viewPrivate') {
                        $query->whereRaw('1=1');
                    }
                }),
            (new Extend\ModelVisibility(Post::class))
                ->scopeAll(function (User $user, Builder $query, string $ability) {
                    if ($ability == 'viewPrivate') {
                        $query->whereRaw('1=1');
                    }
                })
        );

        $this->app();

        $actor = User::find(2);

        $visiblePosts = CommentPost::query()->whereVisibleTo($actor)->get();

        $this->assertCount(2, $visiblePosts);
    }
}
