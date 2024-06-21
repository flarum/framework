<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Likes\Tests\integration\api\discussions;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Group\Group;
use Flarum\Likes\Api\PostResourceFields;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Support\Arr;

class ListPostsTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-likes');

        $this->prepareDatabase([
            Discussion::class => [
                ['id' => 100, 'title' => __CLASS__, 'created_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 101, 'comment_count' => 1],
            ],
            Post::class => [
                ['id' => 101, 'discussion_id' => 100, 'created_at' => Carbon::now(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>text</p></t>'],
            ],
            User::class => [
                $this->normalUser(),
                ['id' => 102, 'username' => 'user102', 'email' => '102@machine.local', 'is_email_confirmed' => 1],
                ['id' => 103, 'username' => 'user103', 'email' => '103@machine.local', 'is_email_confirmed' => 1],
                ['id' => 104, 'username' => 'user104', 'email' => '104@machine.local', 'is_email_confirmed' => 1],
                ['id' => 105, 'username' => 'user105', 'email' => '105@machine.local', 'is_email_confirmed' => 1],
                ['id' => 106, 'username' => 'user106', 'email' => '106@machine.local', 'is_email_confirmed' => 1],
                ['id' => 107, 'username' => 'user107', 'email' => '107@machine.local', 'is_email_confirmed' => 1],
                ['id' => 108, 'username' => 'user108', 'email' => '108@machine.local', 'is_email_confirmed' => 1],
                ['id' => 109, 'username' => 'user109', 'email' => '109@machine.local', 'is_email_confirmed' => 1],
                ['id' => 110, 'username' => 'user110', 'email' => '110@machine.local', 'is_email_confirmed' => 1],
                ['id' => 111, 'username' => 'user111', 'email' => '111@machine.local', 'is_email_confirmed' => 1],
                ['id' => 112, 'username' => 'user112', 'email' => '112@machine.local', 'is_email_confirmed' => 1],
            ],
            'post_likes' => [
                ['user_id' => 102, 'post_id' => 101, 'created_at' => Carbon::now()->addMinutes(2)],
                ['user_id' => 104, 'post_id' => 101, 'created_at' => Carbon::now()->addMinutes(3)],
                ['user_id' => 105, 'post_id' => 101, 'created_at' => Carbon::now()->addMinutes(4)],
                ['user_id' => 106, 'post_id' => 101, 'created_at' => Carbon::now()->addMinutes(5)],
                ['user_id' => 107, 'post_id' => 101, 'created_at' => Carbon::now()->addMinutes(6)],
                ['user_id' => 108, 'post_id' => 101, 'created_at' => Carbon::now()->addMinutes(7)],
                ['user_id' => 109, 'post_id' => 101, 'created_at' => Carbon::now()->addMinutes(8)],
                ['user_id' => 110, 'post_id' => 101, 'created_at' => Carbon::now()->addMinutes(9)],
                ['user_id' => 2, 'post_id' => 101, 'created_at' => Carbon::now()->addMinutes(10)],
                ['user_id' => 111, 'post_id' => 101, 'created_at' => Carbon::now()->addMinutes(11)],
                ['user_id' => 112, 'post_id' => 101, 'created_at' => Carbon::now()->addMinutes(12)],
            ],
            'group_permission' => [
                ['group_id' => Group::GUEST_ID, 'permission' => 'searchUsers'],
            ],
        ]);
    }

    /**
     * @test
     */
    public function liked_filter_works()
    {
        $response = $this->send(
            $this->request('GET', '/api/users')
                ->withQueryParams([
                    'filter' => ['liked' => 101],
                ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getBody()->getContents(), true)['data'];

        // Order-independent comparison
        $ids = Arr::pluck($data, 'id');
        $this->assertEqualsCanonicalizing([
            102, 104, 105, 106, 107, 108, 109, 110, 2, 111, 112
        ], $ids, 'IDs do not match');
    }

    /**
     * @test
     */
    public function liked_filter_works_negated()
    {
        $response = $this->send(
            $this->request('GET', '/api/users')
            ->withQueryParams([
                'filter' => ['-liked' => 101],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getBody()->getContents(), true)['data'];

        // Order-independent comparison
        $ids = Arr::pluck($data, 'id');
        $this->assertEqualsCanonicalizing([1, 103], $ids, 'IDs do not match');
    }

    /** @test */
    public function likes_relation_returns_limited_results_and_shows_only_visible_posts_in_show_post_endpoint()
    {
        // List posts endpoint
        $response = $this->send(
            $this->request('GET', '/api/posts/101', [
                'authenticatedAs' => 2,
            ])->withQueryParams([
                'include' => 'likes',
            ])
        );

        $data = json_decode($response->getBody()->getContents(), true)['data'];

        $this->assertEquals(200, $response->getStatusCode());

        $likes = $data['relationships']['likes']['data'];

        // Only displays a limited amount of likes
        $this->assertCount(PostResourceFields::$maxLikes, $likes);
        // Displays the correct count of likes
        $this->assertEquals(11, $data['attributes']['likesCount']);
        // Of the limited amount of likes, the actor always appears
        $this->assertEquals([2, 102, 104, 105], Arr::pluck($likes, 'id'));
    }

    /** @test */
    public function likes_relation_returns_limited_results_and_shows_only_visible_posts_in_list_posts_endpoint()
    {
        // List posts endpoint
        $response = $this->send(
            $this->request('GET', '/api/posts', [
                'authenticatedAs' => 2,
            ])->withQueryParams([
                'filter' => ['discussion' => 100],
                'include' => 'likes',
            ])
        );

        $data = json_decode($response->getBody()->getContents(), true)['data'];

        $this->assertEquals(200, $response->getStatusCode());

        $likes = $data[0]['relationships']['likes']['data'];

        // Only displays a limited amount of likes
        $this->assertCount(PostResourceFields::$maxLikes, $likes);
        // Displays the correct count of likes
        $this->assertEquals(11, $data[0]['attributes']['likesCount']);
        // Of the limited amount of likes, the actor always appears
        $this->assertEquals([2, 102, 104, 105], Arr::pluck($likes, 'id'));
    }

    /**
     * @dataProvider likesIncludeProvider
     * @test
     */
    public function likes_relation_returns_limited_results_and_shows_only_visible_posts_in_show_discussion_endpoint(?string $include)
    {
        // Show discussion endpoint
        $response = $this->send(
            $this->request('GET', '/api/discussions/100', [
                'authenticatedAs' => 2,
            ])->withQueryParams([
                'include' => $include,
            ])
        );

        $body = $response->getBody()->getContents();

        $this->assertEquals(200, $response->getStatusCode(), $body);

        $included = json_decode($body, true)['included'] ?? [];

        $likes = collect($included)
            ->where('type', 'posts')
            ->where('id', 101)
            ->first()['relationships']['likes']['data'] ?? null;

        // Only displays a limited amount of likes
        $this->assertNotNull($likes, $body);
        $this->assertCount(PostResourceFields::$maxLikes, $likes);
        // Displays the correct count of likes
        $this->assertEquals(11, collect($included)
            ->where('type', 'posts')
            ->where('id', 101)
            ->first()['attributes']['likesCount'] ?? null, $body);
        // Of the limited amount of likes, the actor always appears
        $this->assertEquals([2, 102, 104, 105], Arr::pluck($likes, 'id'), $body);
    }

    public function likesIncludeProvider(): array
    {
        return [
            ['posts,posts.likes'],
            ['posts.likes'],
            [null],
        ];
    }
}
