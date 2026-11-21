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
    #[DataProvider('forumComposerResources')]
    public function creation_includes_the_updated_count_for_a_mentioned_user(string $type): void
    {
        $this->reply('@"potato"#3 first mention');
        $this->assertSame(1, $this->mentionCountOf(3));

        $data = ['type' => $type, 'attributes' => ['content' => '@"potato"#3 second mention']];
        if ($type === 'discussions') {
            $data['attributes']['title'] = 'Mentioning another user';
        } else {
            $data['relationships']['discussion']['data'] = ['type' => 'discussions', 'id' => '2'];
        }

        $response = $this->send($this->request('POST', '/api/'.$type, [
            'authenticatedAs' => 2,
            'json' => ['data' => $data],
        ]));

        $this->assertEquals(201, $response->getStatusCode(), (string) $response->getBody());
        $payload = json_decode($response->getBody(), true);
        $recipients = array_values(array_filter($payload['included'], fn ($item) => $item['type'] === 'users' && $item['id'] === '3'));
        $this->assertCount(1, $recipients);
        $this->assertSame(2, $recipients[0]['attributes']['mentionCount']);
        $this->assertSame(2, $this->mentionCountOf(3));
    }

    public static function forumComposerResources(): iterable
    {
        yield 'reply composer' => ['posts'];
        yield 'discussion composer' => ['discussions'];
    }

    #[Test]
    #[DataProvider('postEditors')]
    public function editing_a_post_includes_updated_counts_for_added_and_removed_mentions(int $editorId): void
    {
        $this->reply('@"potato"#3 first mention');
        $postId = $this->reply('@"potato"#3 second mention', $editorId);
        $this->assertSame(2, $this->mentionCountOf(3));
        Post::query()->whereKey($postId)->update(['created_at' => Carbon::now()->subMinute()]);

        $payload = $this->patchPost($postId, $editorId, ['content' => '@"moderator"#4 edited']);
        $affected = array_column($payload['data']['relationships']['affectedMentionUsers']['data'] ?? [], 'id');
        sort($affected);
        $this->assertSame(['3', '4'], $affected);

        $counts = [];
        foreach ($payload['included'] as $item) {
            if ($item['type'] === 'users' && in_array($item['id'], $affected, true)) {
                $counts[$item['id']] = $item['attributes']['mentionCount'];
            }
        }

        $this->assertSame(1, $counts[3]);
        $this->assertSame(1, $counts[4]);
        $this->assertSame($this->mentionCountOf(3), $counts[3]);
        $this->assertSame($this->mentionCountOf(4), $counts[4]);
    }

    public static function postEditors(): iterable
    {
        yield 'admin' => [1];
        yield 'member' => [2];
    }

    #[Test]
    #[DataProvider('postEditors')]
    public function removing_the_only_mention_reports_the_users_new_count(int $editorId): void
    {
        $postId = $this->reply('@"potato"#3 hello', $editorId);
        $this->assertSame(1, $this->mentionCountOf(3));

        $payload = $this->patchPost($postId, $editorId, ['content' => 'hello']);

        $this->assertSame([3 => 0], $this->affectedMentionCounts($payload));
        $this->assertSame(0, $this->mentionCountOf(3));
    }

    #[Test]
    #[DataProvider('postModerators')]
    public function hiding_and_restoring_a_post_reports_the_users_new_counts(int $moderatorId): void
    {
        $postId = $this->reply('@"potato"#3 hello');

        $payload = $this->patchPost($postId, $moderatorId, ['isHidden' => true]);
        $this->assertSame([3 => 0], $this->affectedMentionCounts($payload));

        $payload = $this->patchPost($postId, $moderatorId, ['isHidden' => false]);
        $this->assertSame([3 => 1], $this->affectedMentionCounts($payload));
    }

    public static function postModerators(): iterable
    {
        yield 'admin' => [1];
        yield 'moderator' => [4];
    }

    #[Test]
    public function approving_an_already_synced_mention_updates_the_count()
    {
        $this->extension('flarum-approval', 'flarum-flags');
        $postId = $this->reply('@"potato"#3 awaiting approval');
        $this->database()->table('posts')->where('id', $postId)->update(['is_private' => true, 'is_approved' => false]);
        $this->app()->getContainer()->make(\Flarum\Mentions\UserMentionCount::class)->refresh([3]);
        $this->assertSame(0, $this->mentionCountOf(3));

        $payload = $this->patchPost($postId, 1, ['isApproved' => true]);
        $this->assertSame([3 => 1], $this->affectedMentionCounts($payload));
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

    protected function reply(string $content, int $userId = 1): int
    {
        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => $userId,
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

    protected function patchPost(int $postId, int $userId, array $attributes): array
    {
        $response = $this->send(
            $this->request('PATCH', "/api/posts/$postId", [
                'authenticatedAs' => $userId,
                'json' => ['data' => ['attributes' => $attributes]],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * The mention counts an update reported for the users it changed, by id.
     *
     * @return array<int, int>
     */
    protected function affectedMentionCounts(array $payload): array
    {
        $affected = array_column($payload['data']['relationships']['affectedMentionUsers']['data'] ?? [], 'id');
        $counts = [];

        foreach ($payload['included'] ?? [] as $item) {
            if ($item['type'] === 'users' && in_array($item['id'], $affected, true)) {
                $counts[(int) $item['id']] = $item['attributes']['mentionCount'];
            }
        }

        ksort($counts);

        return $counts;
    }

    protected function mentionCountOf(int $userId): int
    {
        return (int) User::query()->findOrFail($userId)->mention_count;
    }
}
