<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\posts;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * Relationships of included resources must load once per request, not once
 * per resource.
 *
 * The serializer resolves deferred values in passes. It used to drain the
 * live queue, and relationship values are prepended to it, so each included
 * resource's relationships ran before the next resource had been added: the
 * relationship buffer only ever held one model, and every relationship below
 * the primary data cost one query per resource.
 */
class NestedIncludeQueryCountTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    private const DISCUSSIONS = 8;

    protected function setUp(): void
    {
        parent::setUp();

        $discussions = [];
        $posts = [];

        for ($i = 1; $i <= self::DISCUSSIONS; $i++) {
            // Each discussion opens with a post by user 1; user 2 replies in every one.
            $discussions[] = ['id' => $i, 'title' => "Discussion $i", 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => $i, 'comment_count' => 2];
            $posts[] = ['id' => $i, 'discussion_id' => $i, 'number' => 1, 'created_at' => Carbon::now(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Opening post</p></t>'];
            $posts[] = ['id' => 100 + $i, 'discussion_id' => $i, 'number' => 2, 'created_at' => Carbon::now(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>Reply</p></t>'];
        }

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
            Discussion::class => $discussions,
            Post::class => $posts,
        ]);
    }

    #[Test]
    public function a_relationship_of_included_resources_loads_once_for_the_page()
    {
        $db = $this->database();
        $db->enableQueryLog();
        $db->flushQueryLog();

        $response = $this->send(
            $this->request('GET', '/api/posts', ['authenticatedAs' => 1])
                ->withQueryParams(['filter' => ['author' => 'normal'], 'include' => 'discussion,discussion.firstPost'])
        );

        $queries = $db->getQueryLog();
        $db->flushQueryLog();

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertCount(self::DISCUSSIONS, $body['data']);

        // Every discussion's first post is in the document...
        $included = array_column(array_filter($body['included'], fn ($r) => $r['type'] === 'posts'), 'id');
        sort($included);
        $this->assertEquals(array_map('strval', range(1, self::DISCUSSIONS)), $included);

        // ...from one query, not one per discussion.
        // (Integer keys are inlined into the SQL rather than bound.)
        $firstPostLoads = array_filter(
            array_column($queries, 'query'),
            fn (string $sql) => (bool) preg_match('/from [`"]?posts[`"]? where [`"]?posts[`"]?\.[`"]?id[`"]? in \(/', $sql)
        );

        $this->assertCount(1, $firstPostLoads, 'The included discussions\' first posts load in a single query');
    }
}
