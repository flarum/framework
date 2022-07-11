<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Likes\Tests\integration\api;

use Carbon\Carbon;
use Flarum\Post\CommentPost;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Psr\Http\Message\ResponseInterface;

class LikePostTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-likes');

        $this->prepareDatabase([
            'users' => [
                ['id' => 1, 'username' => 'Muralf', 'email' => 'muralf@machine.local', 'is_email_confirmed' => 1],
                $this->normalUser(),
                ['id' => 3, 'username' => 'Acme', 'email' => 'acme@machine.local', 'is_email_confirmed' => 1],
            ],
            'discussions' => [
                ['id' => 1, 'title' => __CLASS__, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 2],
            ],
            'posts' => [
                ['id' => 1, 'number' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>something</p></t>'],
                ['id' => 3, 'number' => 2, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>something</p></t>'],
                ['id' => 5, 'number' => 3, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 3, 'type' => 'discussionRenamed', 'content' => '<t><p>something</p></t>'],
                ['id' => 6, 'number' => 4, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>something</p></t>'],
            ],
            'groups' => [
                ['id' => 5, 'name_singular' => 'Acme', 'name_plural' => 'Acme', 'is_hidden' => 0],
                ['id' => 6, 'name_singular' => 'Acme1', 'name_plural' => 'Acme1', 'is_hidden' => 0]
            ],
            'group_user' => [
                ['user_id' => 3, 'group_id' => 5]
            ]
        ]);

        $this->database()->table('group_permission')->where('permission', 'discussion.likePosts')->delete();
        $this->database()->table('group_permission')->insert(['permission' => 'discussion.likePosts', 'group_id' => 5]);
    }

    /**
     * @dataProvider allowedUsersToLike
     * @test
     */
    public function can_like_a_post_if_allowed(int $postId, ?int $authenticatedAs, string $message)
    {
        $response = $this->sendLikeRequest($postId, $authenticatedAs);

        $post = CommentPost::query()->find($postId);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotNull($post->likes->where('id', $authenticatedAs)->first(), $message);
    }

    /**
     * @dataProvider unallowedUsersToLike
     * @test
     */
    public function cannot_like_a_post_if_not_allowed(int $postId, ?int $authenticatedAs, string $message)
    {
        $response = $this->sendLikeRequest($postId, $authenticatedAs);

        $post = CommentPost::query()->find($postId);

        $this->assertEquals(403, $response->getStatusCode(), $message);
        $this->assertNull($post->likes->where('id', $authenticatedAs)->first());
    }

    /**
     * @dataProvider allowedUsersToLike
     * @test
     */
    public function can_dislike_a_post_if_liked_and_allowed(int $postId, ?int $authenticatedAs, string $message)
    {
        $this->sendLikeRequest($postId, $authenticatedAs);
        $response = $this->sendLikeRequest($postId, $authenticatedAs, false);

        $post = CommentPost::query()->find($postId);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNull($post->likes->where('id', $authenticatedAs)->first(), $message);
    }

    public function allowedUsersToLike(): array
    {
        return [
            [1, 1, 'Admin can like any post'],
            [1, 3, 'User with permission can like other posts'],
            [6, 3, 'User with permission can like own post']
        ];
    }

    public function unallowedUsersToLike(): array
    {
        return [
            [1, null, 'Guest cannot like any post'],
            [1, 2, 'User without permission cannot like any post']
        ];
    }

    protected function sendLikeRequest(int $postId, ?int $authenticatedAs, bool $liked = true): ResponseInterface
    {
        if (! isset($authenticatedAs)) {
            $initial = $this->send(
                $this->request('GET', '/')
            );

            $token = $initial->getHeaderLine('X-CSRF-Token');
        }

        $request = $this->request('PATCH', "/api/posts/$postId", [
            'authenticatedAs' => $authenticatedAs,
            'cookiesFrom' => $initial ?? null,
            'json' => [
                'data' => [
                    'attributes' => [
                        'isLiked' => $liked
                    ]
                ]
            ]
        ]);

        if (! isset($authenticatedAs)) {
            $request = $request->withHeader('X-CSRF-Token', $token);
        }

        return $this->send($request);
    }
}
