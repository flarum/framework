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

class ShowTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            Discussion::class => [
                ['id' => 1, 'title' => 'Empty discussion', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => null, 'comment_count' => 0, 'is_private' => 0],
                ['id' => 2, 'title' => 'Discussion with post', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 1, 'is_private' => 0],
                ['id' => 3, 'title' => 'Private discussion', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => null, 'comment_count' => 0, 'is_private' => 1],
                ['id' => 4, 'title' => 'Discussion with hidden post', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 2, 'comment_count' => 1, 'is_private' => 0],
                ['id' => 5, 'title' => 'Private discussion with private post', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 3, 'comment_count' => 0, 'is_private' => 1],
                ['id' => 6, 'title' => 'Private discussion with hidden post', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 4, 'comment_count' => 0, 'is_private' => 1],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 2, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>a normal reply - too-obscure</p></t>'],
                ['id' => 2, 'discussion_id' => 4, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>a hidden reply - too-obscure</p></t>', 'hidden_at' => Carbon::now()->toDateTimeString()],
                ['id' => 3, 'discussion_id' => 5, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>a private post</p></t>', 'is_private' => 1],
                ['id' => 4, 'discussion_id' => 6, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>a hidden post</p></t>', 'hidden_at' => Carbon::now()->toDateTimeString()],
                ['id' => 5, 'discussion_id' => 6, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'type' => 'discussionRenamed', 'content' => '["Old title","Private discussion with hidden post"]'],
            ],
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'privileged', 'email' => 'privileged@machine.local', 'is_email_confirmed' => 1],
            ]
        ]);
    }

    #[Test]
    public function author_can_see_discussion()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions/1', [
                'authenticatedAs' => 2,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), $response->getBody()->getContents());
    }

    #[Test]
    public function author_can_see_discussion_via_slug()
    {
        // Note that here, the slug doesn't actually have to match the real slug
        // since the default slugging strategy only takes the numerical part into account
        $response = $this->send(
            $this->request('GET', '/api/discussions/1-fdsafdsajfsakf', [
                'authenticatedAs' => 2,
            ])->withQueryParams([
                'bySlug' => true
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), $response->getBody()->getContents());
    }

    #[Test]
    public function guest_cannot_see_empty_discussion()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions/1')
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    #[Test]
    public function guest_cannot_see_hidden_posts()
    {
        $response = $this->send(
            $this->request('GET', '/api/posts')
                ->withQueryParams([
                    'filter' => ['discussion' => 4]
                ])
        );

        $json = json_decode($response->getBody()->getContents(), true);

        $this->assertEmpty(Arr::get($json, 'data'));
    }

    #[Test]
    public function author_can_see_hidden_posts()
    {
        $response = $this->send(
            $this->request('GET', '/api/posts', [
                'authenticatedAs' => 2,
            ])->withQueryParams([
                'filter' => ['discussion' => 4]
            ])
        );

        $json = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals(2, Arr::get($json, 'data.0.id'), $response->getBody()->getContents());
    }

    #[Test]
    public function guest_can_see_discussion()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions/2')
        );

        $this->assertEquals(200, $response->getStatusCode(), $response->getBody()->getContents());
    }

    #[Test]
    public function guests_cannot_see_private_discussion()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions/3')
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    #[Test]
    public function user_allowed_to_view_private_discussion_and_its_posts_can_see_it_when_its_comments_are_all_private()
    {
        $this->grantViewPrivateDiscussionsToUser(3);
        $this->grantViewPrivatePostsToUser(3);

        $response = $this->send(
            $this->request('GET', '/api/discussions/5', [
                'authenticatedAs' => 3,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), $response->getBody()->getContents());
    }

    #[Test]
    public function user_allowed_to_view_private_discussion_but_not_its_private_posts_cannot_see_it()
    {
        $this->grantViewPrivateDiscussionsToUser(3);

        $response = $this->send(
            $this->request('GET', '/api/discussions/5', [
                'authenticatedAs' => 3,
            ])
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    #[Test]
    public function user_allowed_to_view_private_discussion_cannot_see_it_when_its_comments_are_all_hidden()
    {
        $this->grantViewPrivateDiscussionsToUser(3);

        $response = $this->send(
            $this->request('GET', '/api/discussions/6', [
                'authenticatedAs' => 3,
            ])
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    #[Test]
    public function user_allowed_to_view_private_discussion_cannot_see_it_when_it_has_no_posts()
    {
        $this->grantViewPrivateDiscussionsToUser(3);

        $response = $this->send(
            $this->request('GET', '/api/discussions/3', [
                'authenticatedAs' => 3,
            ])
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    #[Test]
    public function user_allowed_to_view_private_discussions_still_cannot_see_empty_public_discussion()
    {
        $this->grantViewPrivateDiscussionsToUser(3);

        $response = $this->send(
            $this->request('GET', '/api/discussions/1', [
                'authenticatedAs' => 3,
            ])
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    private function grantViewPrivatePostsToUser(int $userId): void
    {
        $this->extend(
            (new Extend\ModelVisibility(Post::class))
                ->scope(function (User $actor, Builder $query) use ($userId) {
                    if ($actor->id === $userId) {
                        $query->orWhereRaw('1=1');
                    }
                }, 'viewPrivate')
        );
    }

    private function grantViewPrivateDiscussionsToUser(int $userId): void
    {
        $this->extend(
            (new Extend\ModelVisibility(Discussion::class))
                ->scope(function (User $actor, Builder $query) use ($userId) {
                    if ($actor->id === $userId) {
                        $query->orWhereRaw('1=1');
                    }
                }, 'viewPrivate')
        );
    }
}
