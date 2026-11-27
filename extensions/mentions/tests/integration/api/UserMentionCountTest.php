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
use Flarum\Mentions\UserMentionCount;
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
            // These tests are about keeping the number in step, not about who
            // may read it (that is UserMentionCountVisibilityTest). Several
            // read the count of another user out of the same payload, as the
            // member and moderator editors do, so those groups hold the
            // permission here.
            'group_permission' => [
                ['group_id' => Group::MEMBER_ID, 'permission' => 'user.viewMentionCount'],
                ['group_id' => Group::MODERATOR_ID, 'permission' => 'user.viewMentionCount'],
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
    public function editing_a_post_without_changing_its_mentions_reports_an_empty_set()
    {
        $postId = $this->reply('@"potato"#3 hello', 2);
        $this->agePostPastTheFloodThrottle($postId);

        $payload = $this->patchPost($postId, 2, ['content' => '@"potato"#3 hello, corrected']);

        // The client decides not to reload a Mentions tab from this attribute,
        // so an edit that changes no count must send it, empty, rather than omit
        // it: an absent attribute means "cannot say" and reloads.
        $this->assertSame([], $payload['data']['attributes']['affectedMentionUsers'] ?? null);
    }

    #[Test]
    public function editing_with_an_explicit_user_include_still_reports_the_affected_counts()
    {
        $postId = $this->reply('@"potato"#3 hello', 2);
        $this->agePostPastTheFloodThrottle($postId);

        // Naming `include=user` replaces the endpoint's default includes. The
        // affected users are an attribute, so they are still reported with
        // their new counts rather than leaving the client unable to say whether
        // the tab on screen must reload.
        $payload = $this->patchPost($postId, 2, ['content' => 'hello'], 'user');
        $this->assertSame([3 => 0], $this->affectedMentionCounts($payload));

        $this->agePostPastTheFloodThrottle($postId);

        // A save that changes no count says so, empty, with the same include.
        $payload = $this->patchPost($postId, 2, ['content' => 'hello, corrected'], 'user');
        $this->assertSame([], $payload['data']['attributes']['affectedMentionUsers'] ?? null);
    }

    #[Test]
    public function an_editor_without_the_count_permission_still_learns_which_users_changed()
    {
        // A member may edit their own post but may not read another user's
        // stored count, which includes mentions they cannot see. The response
        // still names the user whose list changed; the count is left out.
        $this->database()->table('group_permission')
            ->where('group_id', Group::MEMBER_ID)
            ->where('permission', 'user.viewMentionCount')
            ->delete();

        $postId = $this->reply('@"potato"#3 hello', 2);
        $this->agePostPastTheFloodThrottle($postId);

        $payload = $this->patchPost($postId, 2, ['content' => 'hello']);
        $affected = $payload['data']['attributes']['affectedMentionUsers'] ?? null;

        $this->assertIsArray($affected, 'The update must always report the affected users as an attribute.');
        $this->assertSame(['3'], array_column($affected, 'id'));
        $this->assertArrayNotHasKey('mentionCount', $affected[0]);
        $this->assertArrayNotHasKey('mentionCountRevision', $affected[0], 'The revision must be hidden whenever the count is.');
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
        $freshAttributes = json_decode($fresh->getBody(), true)['data']['attributes'];
        $this->assertSame(1, $freshAttributes['mentionCount']);
        $this->assertSame($freshAttributes['mentionCount'], $users[0]['attributes']['mentionCount']);
        $this->assertSame(
            $freshAttributes['mentionCountRevision'],
            $users[0]['attributes']['mentionCountRevision'],
            'The included author must carry the revision of the count it carries, or the forum could drop the new count as older.'
        );
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
        $this->agePostPastTheFloodThrottle($postId);

        $payload = $this->patchPost($postId, $editorId, ['content' => '@"moderator"#4 edited']);

        $counts = $this->affectedMentionCounts($payload);
        $this->assertSame(
            [3 => 1, 4 => 1],
            $counts,
            'The response must report both the user the post stopped mentioning and the one it started mentioning.'
        );
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

        // A content edit is throttled while the author's post is fresh; age it
        // first so this test is about the mention count, not about flooding.
        $this->agePostPastTheFloodThrottle($postId);

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
    public function a_combined_edit_and_hide_reports_the_former_and_new_recipients(): void
    {
        $this->reply('@"potato"#3 first mention');
        $postId = $this->reply('@"potato"#3 second mention');
        $this->assertSame(2, $this->mentionCountOf(3));
        $this->agePostPastTheFloodThrottle($postId);

        // One save replaces the mention of potato with moderator and hides the
        // post. Both recipients' counts change, and the hide is handled after
        // the content, so reporting only what the hide listener found would
        // leave potato's count stale on the client.
        $payload = $this->patchPost($postId, 1, [
            'content' => '@"moderator"#4 edited',
            'isHidden' => true,
        ]);

        $this->assertSame(
            [3 => 1, 4 => 0],
            $this->affectedMentionCounts($payload),
            'The response must name both the user the post stopped mentioning and the one it started mentioning.'
        );
        $this->assertSame(1, $this->mentionCountOf(3), 'Hiding the post must also drop the count of the mention it removed.');
    }

    #[Test]
    public function every_count_change_raises_the_revision_and_each_response_carries_the_new_one(): void
    {
        $this->app();
        $this->assertSame(0, $this->mentionCountRevisionOf(3));

        $revisions = [];

        // A reply that mentions potato: the creation response side-loads the
        // user with the count and revision that save wrote.
        $response = $this->send($this->request('POST', '/api/posts', [
            'authenticatedAs' => 1,
            'json' => ['data' => [
                'type' => 'posts',
                'attributes' => ['content' => '@"potato"#3 first mention'],
                'relationships' => ['discussion' => ['data' => ['type' => 'discussions', 'id' => '2']]],
            ]],
        ]));
        $this->assertEquals(201, $response->getStatusCode(), (string) $response->getBody());
        $payload = json_decode((string) $response->getBody(), true);
        $firstPostId = (int) $payload['data']['id'];
        $recipient = array_values(array_filter($payload['included'], fn ($item) => $item['type'] === 'users' && $item['id'] === '3'))[0];
        $this->assertSame([1, $this->mentionCountRevisionOf(3)], [$recipient['attributes']['mentionCount'], $recipient['attributes']['mentionCountRevision']]);
        $revisions[] = $recipient['attributes']['mentionCountRevision'];

        $secondPostId = $this->reply('@"potato"#3 second mention');
        $revisions[] = $this->mentionCountRevisionOf(3);

        // An edit that removes a mention, and a hide: each update response
        // reports the revision of the count it carries.
        $this->agePostPastTheFloodThrottle($firstPostId);
        $payload = $this->patchPost($firstPostId, 1, ['content' => 'no mention']);
        $this->assertSame(['3' => [1, $this->mentionCountRevisionOf(3)]], $this->affectedMentionCountsAndRevisions($payload));
        $revisions[] = $this->mentionCountRevisionOf(3);

        $payload = $this->patchPost($secondPostId, 1, ['isHidden' => true]);
        $this->assertSame(['3' => [0, $this->mentionCountRevisionOf(3)]], $this->affectedMentionCountsAndRevisions($payload));
        $revisions[] = $this->mentionCountRevisionOf(3);

        // A profile read carries the pair as it is stored.
        $response = $this->send($this->request('GET', '/api/users/3', ['authenticatedAs' => 3]));
        $attributes = json_decode((string) $response->getBody(), true)['data']['attributes'];
        $this->assertSame([0, end($revisions)], [$attributes['mentionCount'], $attributes['mentionCountRevision']]);

        $sorted = $revisions;
        sort($sorted);
        $this->assertSame($sorted, $revisions, 'Each count change must raise the revision.');
        $this->assertCount(count($revisions), array_unique($revisions), 'No two count changes may share a revision: '.implode(', ', $revisions));
    }

    #[Test]
    public function a_recount_raises_each_users_revision_by_exactly_one(): void
    {
        $this->reply('@"potato"#3 hello');
        $before = [3 => $this->mentionCountRevisionOf(3), 4 => $this->mentionCountRevisionOf(4)];

        // The same user listed twice, and a recount that leaves the count as it
        // was: the revision still moves forward, by one.
        $this->app()->getContainer()->make(UserMentionCount::class)->refresh([3, '3', 4]);

        $this->assertSame($before[3] + 1, $this->mentionCountRevisionOf(3));
        $this->assertSame($before[4] + 1, $this->mentionCountRevisionOf(4));
        $this->assertSame(1, $this->mentionCountOf(3));
        $this->assertSame(0, $this->mentionCountOf(4));
    }

    #[Test]
    public function approving_a_post_that_was_waiting_for_approval_updates_the_count()
    {
        $this->extension('flarum-approval', 'flarum-flags');

        // Without this permission a member's reply is held for approval, so it
        // starts out private: its mention of potato exists, but does not count.
        $this->database()->table('group_permission')
            ->where('group_id', Group::MEMBER_ID)
            ->where('permission', 'discussion.replyWithoutApproval')
            ->delete();

        $postId = $this->reply('@"potato"#3 awaiting approval', 2);
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

    /**
     * Backdate a post so its author is no longer flood-throttled when editing it.
     *
     * Core's PostCreationThrottler treats a `posts.update` that changes `content`
     * as another parse of the post (mentions resolved, content rendered), and
     * throttles it when any of the actor's posts were created or edited within
     * its window. These tests reply and patch straight away, so the post is
     * aged past that window instead of waiting for it.
     */
    protected function agePostPastTheFloodThrottle(int $postId): void
    {
        $old = Carbon::now()->subMinute();

        $this->database()->table('posts')->where('id', $postId)->update([
            'created_at' => $old,
            'edited_at' => $old,
        ]);
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

    protected function patchPost(int $postId, int $userId, array $attributes, ?string $include = null): array
    {
        $request = $this->request('PATCH', "/api/posts/$postId", [
            'authenticatedAs' => $userId,
            'json' => ['data' => ['attributes' => $attributes]],
        ]);

        if ($include !== null) {
            $request = $request->withQueryParams(['include' => $include]);
        }

        $response = $this->send($request);

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
        $counts = [];

        foreach ($payload['data']['attributes']['affectedMentionUsers'] ?? [] as $item) {
            $counts[(int) $item['id']] = $item['mentionCount'];
        }

        ksort($counts);

        return $counts;
    }

    /**
     * The count and revision an update reported for each user it changed, by id.
     *
     * @return array<string, array{int, int}>
     */
    protected function affectedMentionCountsAndRevisions(array $payload): array
    {
        $pairs = [];

        foreach ($payload['data']['attributes']['affectedMentionUsers'] ?? [] as $item) {
            $pairs[$item['id']] = [$item['mentionCount'], $item['mentionCountRevision']];
        }

        return $pairs;
    }

    protected function mentionCountRevisionOf(int $userId): int
    {
        return (int) User::query()->findOrFail($userId)->mention_count_revision;
    }

    protected function mentionCountOf(int $userId): int
    {
        return (int) User::query()->findOrFail($userId)->mention_count;
    }
}
