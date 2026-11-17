<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Tests\integration\api;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class UserMentionCountTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-mentions');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'potato', 'email' => 'potato@machine.local', 'is_email_confirmed' => 1],
            ],
            Discussion::class => [
                ['id' => 2, 'title' => __CLASS__, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 1, 'last_post_number' => 1],
            ],
            Post::class => [
                ['id' => 1, 'number' => 1, 'discussion_id' => 2, 'created_at' => Carbon::now(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>first</p></t>'],
            ],
        ]);
    }

    #[Test]
    public function mentioning_a_user_counts_the_post()
    {
        $this->reply('@"potato"#3 hello');
        $this->reply('@"potato"#3 and again');

        $this->assertSame(2, $this->mentionCountOf(3));
    }

    #[Test]
    public function mention_count_is_shown_on_the_user()
    {
        $this->reply('@"potato"#3 hello');

        $response = $this->send(
            $this->request('GET', '/api/users/3', ['authenticatedAs' => 2])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame(1, json_decode($response->getBody(), true)['data']['attributes']['mentionCount']);
    }

    #[Test]
    public function editing_the_mention_out_updates_the_count()
    {
        $postId = $this->reply('@"potato"#3 hello');
        $this->assertSame(1, $this->mentionCountOf(3));

        $this->patchPost($postId, 1, ['content' => 'hello']);

        $this->assertSame(0, $this->mentionCountOf(3));
    }

    #[Test]
    public function hiding_and_restoring_the_post_updates_the_count()
    {
        $postId = $this->reply('@"potato"#3 hello');

        $this->patchPost($postId, 1, ['isHidden' => true]);
        $this->assertSame(0, $this->mentionCountOf(3));

        $response = $this->send($this->request('GET', '/api/users/3', ['authenticatedAs' => 3]));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame(0, json_decode($response->getBody(), true)['data']['attributes']['mentionCount']);
        $response = $this->send($this->request('GET', '/api/posts', ['authenticatedAs' => 3])
            ->withQueryParams(['filter' => ['type' => 'comment', 'mentioned' => '3']]));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame([], json_decode($response->getBody(), true)['data']);

        $this->patchPost($postId, 1, ['isHidden' => false]);
        $this->assertSame(1, $this->mentionCountOf(3));
    }

    #[Test]
    public function deleting_the_post_updates_the_count()
    {
        $postId = $this->reply('@"potato"#3 hello');
        $this->assertSame(1, $this->mentionCountOf(3));

        $response = $this->send(
            $this->request('DELETE', "/api/posts/$postId", ['authenticatedAs' => 1])
        );

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertSame(0, $this->mentionCountOf(3));
    }

    #[Test]
    public function editing_a_hidden_post_does_not_restore_its_count()
    {
        $postId = $this->reply('@"potato"#3 hello');
        $this->patchPost($postId, 1, ['isHidden' => true]);
        $this->patchPost($postId, 1, ['content' => '@"potato"#3 edited']);
        $this->assertSame(0, $this->mentionCountOf(3));

        $response = $this->send($this->request('GET', '/api/users/3', ['authenticatedAs' => 3]));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame(0, json_decode($response->getBody(), true)['data']['attributes']['mentionCount']);
        $response = $this->send($this->request('GET', '/api/posts', ['authenticatedAs' => 3])
            ->withQueryParams(['filter' => ['type' => 'comment', 'mentioned' => '3']]));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame([], json_decode($response->getBody(), true)['data']);

        $this->patchPost($postId, 1, ['isHidden' => false]);
        $this->assertSame(1, $this->mentionCountOf(3));
    }

    #[Test]
    public function a_self_mention_is_current_in_the_creation_response()
    {
        $response = $this->send($this->request('POST', '/api/posts', [
            'authenticatedAs' => 1,
            'json' => ['data' => [
                'type' => 'posts',
                'attributes' => ['content' => '@"admin"#1 hello'],
                'relationships' => ['discussion' => ['data' => ['type' => 'discussions', 'id' => 2]]],
            ]],
        ]));
        $this->assertEquals(201, $response->getStatusCode(), (string) $response->getBody());
        $payload = json_decode($response->getBody(), true);
        $users = array_values(array_filter($payload['included'], fn ($item) => $item['type'] === 'users' && $item['id'] === '1'));
        $this->assertNotEmpty($users);
        $this->assertSame(1, $users[0]['attributes']['mentionCount']);
        $this->assertSame(1, $this->mentionCountOf(1));
    }

    #[Test]
    public function approving_an_already_synced_mention_updates_the_count()
    {
        $this->extension('flarum-approval', 'flarum-flags');
        $postId = $this->reply('@"potato"#3 awaiting approval');
        $this->database()->table('posts')->where('id', $postId)->update(['is_private' => true, 'is_approved' => false]);
        $this->app()->getContainer()->make(\Flarum\Mentions\UserMentionCount::class)->refresh([3]);
        $this->assertSame(0, $this->mentionCountOf(3));

        $this->patchPost($postId, 1, ['isApproved' => true]);
        $this->assertSame(1, $this->mentionCountOf(3));
        $response = $this->send($this->request('GET', '/api/users/3', ['authenticatedAs' => 3]));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame(1, json_decode($response->getBody(), true)['data']['attributes']['mentionCount']);
    }

    #[Test]
    public function refreshing_a_loaded_author_does_not_leave_a_dirty_counter()
    {
        $this->app();
        $author = User::query()->findOrFail(1);
        $postId = $this->reply('@"admin"#1 hello');
        $post = Post::query()->findOrFail($postId);
        $post->setRelation('user', $author);
        $this->app()->getContainer()->make(\Flarum\Mentions\Listener\UpdateMentionsMetadataWhenVisible::class)
            ->handle(new \Flarum\Post\Event\Restored($post));

        $this->assertSame(1, (int) $author->mention_count);
        $this->assertFalse($author->isDirty('mention_count'));
        $this->database()->table('users')->where('id', 1)->update(['mention_count' => 2]);
        $author->last_seen_at = Carbon::now();
        $author->save();
        $this->assertSame(2, $this->mentionCountOf(1));
    }

    protected function reply(string $content): int
    {
        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => ['content' => $content],
                        'relationships' => [
                            'discussion' => ['data' => ['type' => 'discussions', 'id' => 2]],
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(201, $response->getStatusCode(), (string) $response->getBody());

        return (int) json_decode($response->getBody(), true)['data']['id'];
    }

    protected function patchPost(int $postId, int $userId, array $attributes): void
    {
        $response = $this->send(
            $this->request('PATCH', "/api/posts/$postId", [
                'authenticatedAs' => $userId,
                'json' => ['data' => ['attributes' => $attributes]],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
    }

    protected function mentionCountOf(int $userId): int
    {
        return (int) User::query()->findOrFail($userId)->mention_count;
    }
}
