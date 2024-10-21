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
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
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
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 2, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>a normal reply - too-obscure</p></t>'],
                ['id' => 2, 'discussion_id' => 4, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>a hidden reply - too-obscure</p></t>', 'hidden_at' => Carbon::now()->toDateTimeString()],
            ],
            User::class => [
                $this->normalUser(),
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
}
