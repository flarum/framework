<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Deck\Tests\integration;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Flags\Flag;
use Flarum\Group\Group;
use Flarum\Post\Post;
use Flarum\Tags\Tag;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Post columns ask for posts as the forum does when it loads more of a
 * discussion: the posts endpoint with its default includes, which extensions
 * add to. Every post must arrive with those (likes, flags, the author's
 * groups), and loading them must not cost a query per post: the integration
 * harness fails any request whose queries grow with the posts listed.
 */
class ColumnQueriesTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    private const POSTS = 12;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags', 'flarum-likes', 'flarum-flags', 'flarum-mentions', 'flarum-deck');

        $users = [];
        $groupUser = [];

        foreach (range(2, 7) as $id) {
            $users[] = ['id' => $id, 'username' => "member$id", 'email' => "member$id@machine.local", 'is_email_confirmed' => 1];
            $groupUser[] = ['group_id' => 5, 'user_id' => $id];
        }

        $discussions = [];
        $discussionTags = [];
        $posts = [];
        $likes = [];
        $flags = [];

        foreach (range(1, self::POSTS) as $id) {
            $author = 2 + $id % 6;

            $discussions[] = ['id' => $id, 'title' => "Discussion $id", 'user_id' => $author, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'first_post_id' => $id, 'comment_count' => 1, 'last_post_number' => 1];
            $discussionTags[] = ['discussion_id' => $id, 'tag_id' => 1 + $id % 3];
            $posts[] = ['id' => $id, 'number' => 1, 'discussion_id' => $id, 'user_id' => $author, 'created_at' => Carbon::now()->subMinutes($id), 'type' => 'comment', 'content' => "<t><p>Post $id</p></t>"];

            foreach ([2, 3] as $liker) {
                $likes[] = ['post_id' => $id, 'user_id' => $liker + $id % 4];
            }

            $flags[] = ['id' => $id, 'post_id' => $id, 'user_id' => 2 + ($id + 1) % 6, 'type' => 'user', 'created_at' => Carbon::now()->subMinutes($id)];
        }

        $this->prepareDatabase([
            User::class => $users,
            Group::class => [
                ['id' => 5, 'name_singular' => 'Regular', 'name_plural' => 'Regulars', 'is_hidden' => 0],
            ],
            'group_user' => $groupUser,
            Tag::class => [
                ['id' => 1, 'name' => 'One', 'slug' => 'one', 'position' => 0],
                ['id' => 2, 'name' => 'Two', 'slug' => 'two', 'position' => 1],
                ['id' => 3, 'name' => 'Three', 'slug' => 'three', 'position' => 2],
            ],
            Discussion::class => $discussions,
            'discussion_tag' => $discussionTags,
            Post::class => $posts,
            'post_likes' => $likes,
            Flag::class => $flags,
        ]);
    }

    /**
     * @return array<string, array{array<string, string>}>
     */
    public static function columns(): array
    {
        return [
            'all posts' => [[]],
            'posts by a group' => [['authorGroup' => '5']],
            'flagged posts' => [['flagged' => '1']],
        ];
    }

    /**
     * @param array<string, string> $filter
     */
    #[Test]
    #[DataProvider('columns')]
    public function posts_come_with_what_the_forum_gives_them_in_a_fixed_number_of_queries(array $filter): void
    {
        // As Deck's PostListState asks: comments, newest first, no total, no include.
        $response = $this->send(
            $this->request('GET', '/api/posts', ['authenticatedAs' => 1])->withQueryParams([
                'filter' => ['type' => 'comment'] + $filter,
                'sort' => '-createdAt',
                'page' => ['limit' => 20, 'total' => '0'],
            ])
        );

        $body = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertCount(self::POSTS, $body['data']);

        foreach ($body['data'] as $post) {
            foreach (['user', 'discussion', 'likes', 'flags'] as $relationship) {
                $this->assertArrayHasKey($relationship, $post['relationships'], "Post {$post['id']} has no $relationship.");
            }
        }

        $included = collect($body['included'])->groupBy('type')->map->count();

        $this->assertGreaterThan(0, $included['groups'] ?? 0, 'Authors come with their groups.');
        $this->assertGreaterThan(0, $included['flags'] ?? 0);
    }
}
