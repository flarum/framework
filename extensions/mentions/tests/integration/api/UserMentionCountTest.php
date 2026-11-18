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
use Flarum\Group\Group;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class UserMentionCountTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-mentions');
        $createdAt = Carbon::now()->subHour();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'potato', 'email' => 'potato@machine.local', 'is_email_confirmed' => 1],
                ['id' => 4, 'username' => 'moderator', 'email' => 'moderator@machine.local', 'is_email_confirmed' => 1],
            ],
            'group_user' => [
                ['user_id' => 4, 'group_id' => Group::MODERATOR_ID],
            ],
            Discussion::class => [
                ['id' => 2, 'title' => __CLASS__, 'created_at' => $createdAt, 'last_posted_at' => $createdAt, 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 1, 'last_post_number' => 1],
            ],
            Post::class => [
                ['id' => 1, 'number' => 1, 'discussion_id' => 2, 'created_at' => $createdAt, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>first</p></t>'],
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
    #[DataProvider('selfMentionCreationRequests')]
    public function a_self_mention_is_current_in_the_creation_response(int $userId, string $username, string $type, bool $includeUser)
    {
        $this->app();
        $this->assertSame(0, $this->mentionCountOf($userId));

        $data = ['type' => $type, 'attributes' => ['content' => sprintf('@"%s"#%d hello', $username, $userId)]];
        if ($type === 'discussions') {
            $data['attributes']['title'] = 'Self-mention in a new discussion';
        } else {
            $data['relationships']['discussion']['data'] = ['type' => 'discussions', 'id' => '2'];
        }

        $path = '/api/'.$type.($includeUser ? '?include=user' : '');
        $request = $this->request('POST', $path, ['authenticatedAs' => $userId, 'json' => ['data' => $data]]);
        if ($includeUser) {
            $request = $request->withQueryParams(['include' => 'user']);
        }

        $response = $this->send($request);
        $this->assertEquals(201, $response->getStatusCode(), (string) $response->getBody());
        $payload = json_decode($response->getBody(), true);
        $author = $payload['data']['relationships']['user']['data'];
        $this->assertSame((string) $userId, $author['id']);
        $users = array_values(array_filter($payload['included'], fn ($item) => $item['type'] === $author['type'] && $item['id'] === $author['id']));
        $this->assertCount(1, $users);

        $fresh = $this->send($this->request('GET', '/api/users/'.$userId, ['authenticatedAs' => $userId]));
        $this->assertEquals(200, $fresh->getStatusCode(), (string) $fresh->getBody());
        $freshCount = json_decode($fresh->getBody(), true)['data']['attributes']['mentionCount'];
        $this->assertSame(1, $freshCount);
        $this->assertSame($freshCount, $users[0]['attributes']['mentionCount']);
    }

    public static function selfMentionCreationRequests(): iterable
    {
        $authors = ['member' => [2, 'normal'], 'moderator' => [4, 'moderator'], 'admin' => [1, 'admin']];

        foreach ($authors as $role => [$id, $username]) {
            foreach (['discussions', 'posts'] as $type) {
                yield "$role $type with explicit user include" => [$id, $username, $type, true];
                yield "$role $type with default includes" => [$id, $username, $type, false];
            }
        }
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
