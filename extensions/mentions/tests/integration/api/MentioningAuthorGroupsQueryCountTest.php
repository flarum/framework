<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Tests\integration\api;

use Carbon\Carbon;
use Flarum\Api\Resource\UserResource;
use Flarum\Api\Schema;
use Flarum\Discussion\Discussion;
use Flarum\Extend;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * Serializing a user can read their groups: policies do (isAdmin(),
 * hasPermission()), and so do fields extensions add to users. The authors of
 * the replies that mention a listed post are loaded with those replies, so
 * their groups have to be loaded with them, or each is looked up on its own.
 */
class MentioningAuthorGroupsQueryCountTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * Below the repeated-query threshold, so a failure reports this test's own
     * assertion rather than the harness's N+1 detector.
     */
    protected const POSTS = 3;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-mentions');

        $this->extend(
            (new Extend\ApiResource(UserResource::class))
                ->fields(fn () => [
                    Schema\Integer::make('groupCount')
                        ->get(fn (User $user) => $user->groups->count()),
                ])
        );

        $posts = [];
        $users = [$this->normalUser(), ['id' => 3, 'username' => 'author', 'email' => 'author@machine.local', 'is_email_confirmed' => 1]];
        $mentions = [];

        // The author writes post k; user 10 + k, who appears nowhere else,
        // replies to it, mentioning it.
        for ($k = 1; $k <= self::POSTS; $k++) {
            $posts[] = ['id' => $k, 'discussion_id' => 1, 'number' => 2 * $k - 1, 'created_at' => Carbon::now()->subMinutes(10 - $k), 'user_id' => 3, 'type' => 'comment', 'content' => '<t><p>Post</p></t>'];
            $posts[] = [
                'id' => 10 + $k, 'discussion_id' => 1, 'number' => 2 * $k, 'created_at' => Carbon::now()->subMinutes(10 - $k), 'user_id' => 10 + $k, 'type' => 'comment',
                'content' => '<r><POSTMENTION displayname="author" id="'.$k.'" number="'.(2 * $k - 1).'" discussionid="1">@"author"#p'.$k.'</POSTMENTION> Thanks</r>',
            ];
            $users[] = ['id' => 10 + $k, 'username' => "replier$k", 'email' => "replier$k@machine.local", 'is_email_confirmed' => 1];
            $mentions[] = ['post_id' => 10 + $k, 'mentions_post_id' => $k];
        }

        $this->prepareDatabase([
            User::class => $users,
            Discussion::class => [
                ['id' => 1, 'title' => 'Discussion', 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 3, 'first_post_id' => 1, 'comment_count' => 2 * self::POSTS, 'last_post_number' => 2 * self::POSTS],
            ],
            Post::class => $posts,
            'post_mentions_post' => $mentions,
        ]);
    }

    #[Test]
    public function the_authors_of_mentioning_replies_have_their_groups_loaded_together(): void
    {
        // The first request pays for what the app caches (settings and the
        // like); compare two that don't.
        $this->listProfilePosts(2);

        $few = $this->listProfilePosts(2);
        $many = $this->listProfilePosts(self::POSTS);

        $this->assertSame(
            $few,
            $many,
            'Listing posts mentioned by more repliers ran more group lookups.'
        );
    }

    /**
     * The request PostsUserPage's PostListState sends, and the number of
     * queries it ran that read users' groups.
     */
    protected function listProfilePosts(int $limit): int
    {
        $db = $this->database();
        $db->enableQueryLog();

        $response = $this->send(
            $this->request('GET', '/api/posts', ['authenticatedAs' => 2])
                ->withQueryParams([
                    'filter' => ['author' => 'author', 'type' => 'comment'],
                    'include' => 'user,discussion',
                    'withDefaultIncludes' => '1',
                    'sort' => '-createdAt',
                    'page' => ['limit' => $limit, 'total' => '0'],
                ])
        );

        $queries = $db->getQueryLog();
        $db->flushQueryLog();

        $body = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode(), $body);

        $document = json_decode($body, true);

        $this->assertCount($limit, $document['data']);

        // Every listed post still comes with the replier who mentions it.
        $users = collect($document['included'])->where('type', 'users')->keyBy('id');

        foreach ($document['data'] as $post) {
            $replier = (string) (10 + (int) $post['id']);

            $this->assertSame([['type' => 'posts', 'id' => $replier]], $post['relationships']['mentionedBy']['data'] ?? null);
            $this->assertArrayHasKey($replier, $users->all(), "Replier $replier is not included.");
        }

        return collect($queries)->filter(fn (array $query) => str_contains($query['query'], 'group_user'))->count();
    }
}
