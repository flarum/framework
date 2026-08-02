<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Sticky\tests\integration\api;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class StickyExcerptTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-sticky');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Pinned', 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1, 'is_sticky' => true, 'last_post_number' => 1],
                ['id' => 2, 'title' => 'Plain one', 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 2, 'comment_count' => 1, 'is_sticky' => false, 'last_post_number' => 1],
                ['id' => 3, 'title' => 'Plain two', 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 3, 'comment_count' => 1, 'is_sticky' => false, 'last_post_number' => 1],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 1, 'type' => 'comment', 'number' => 1, 'content' => '<t><p>Welcome to the <STRONG><s>**</s>forum<e>**</e></STRONG> everyone</p></t>'],
                ['id' => 2, 'discussion_id' => 2, 'created_at' => Carbon::now(), 'user_id' => 1, 'type' => 'comment', 'number' => 1, 'content' => '<t><p>An ordinary discussion</p></t>'],
                ['id' => 3, 'discussion_id' => 3, 'created_at' => Carbon::now(), 'user_id' => 1, 'type' => 'comment', 'number' => 1, 'content' => '<t><p>Another ordinary discussion</p></t>'],
            ],
        ]);
    }

    /**
     * @return array{0: mixed, 1: string[]} decoded body and the SQL that touched the posts table
     */
    protected function listDiscussions(): array
    {
        $db = $this->database();
        $db->enableQueryLog();
        $db->flushQueryLog();

        $response = $this->send(
            $this->request('GET', '/api/discussions', ['authenticatedAs' => 2])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $table = 'from '.$db->getTablePrefix().'posts';
        $postsQueries = array_values(array_filter(
            array_column($db->getQueryLog(), 'query'),
            fn (string $sql) => str_contains(str_replace(['`', '"'], '', $sql), $table)
        ));
        $db->flushQueryLog();

        return [json_decode($response->getBody()->getContents(), true), $postsQueries];
    }

    #[Test]
    public function the_index_no_longer_serializes_first_posts(): void
    {
        [$body] = $this->listDiscussions();

        $includedPosts = array_filter($body['included'] ?? [], fn (array $r) => $r['type'] === 'posts');

        // Serializing an included post renders its full HTML through the
        // formatter and runs its visibility policies — for a plain-text
        // excerpt shown only on stickied rows. The excerpt is an attribute
        // now; no posts belong in the list payload.
        $this->assertCount(0, $includedPosts, 'The discussion list must not serialize posts.');
    }

    #[Test]
    public function sticky_discussions_carry_a_plain_text_excerpt(): void
    {
        [$body] = $this->listDiscussions();

        $byId = [];
        foreach ($body['data'] as $discussion) {
            $byId[$discussion['id']] = $discussion['attributes'];
        }

        // Formatting is stripped, not rendered: no tags, no markdown syntax.
        $this->assertSame('Welcome to the forum everyone', $byId['1']['firstPostExcerpt'] ?? null);

        // Non-sticky rows don't pay for or expose an excerpt.
        $this->assertArrayNotHasKey('firstPostExcerpt', $byId['2']);
        $this->assertArrayNotHasKey('firstPostExcerpt', $byId['3']);
    }

    #[Test]
    public function the_excerpt_costs_one_constrained_posts_query(): void
    {
        [, $postsQueries] = $this->listDiscussions();

        $this->assertCount(
            1,
            $postsQueries,
            "Expected exactly one batched posts query for sticky excerpts. Ran:\n".implode("\n", $postsQueries)
        );
    }

    #[Test]
    public function explicitly_requesting_first_posts_still_serializes_them_for_every_row(): void
    {
        // Clients may include firstPost themselves (fof/synopsis does, for
        // excerpts on ALL discussions). The excerpt optimisation must not
        // interfere: pre-loading a constrained firstPost relation would make
        // the include serialize null for non-sticky rows.
        $response = $this->send(
            $this->request('GET', '/api/discussions', ['authenticatedAs' => 2])
                ->withQueryParams(['include' => 'firstPost'])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true);

        $includedPosts = array_filter($body['included'] ?? [], fn (array $r) => $r['type'] === 'posts');
        $this->assertCount(3, $includedPosts, 'All three first posts must be included when explicitly requested.');

        foreach ($body['data'] as $discussion) {
            $this->assertSame(
                (string) $discussion['id'],
                $discussion['relationships']['firstPost']['data']['id'] ?? null,
                "Discussion {$discussion['id']} lost its firstPost linkage."
            );
        }
    }

    #[Test]
    public function disabling_the_excerpt_setting_removes_the_posts_query_entirely(): void
    {
        $this->setting('flarum-sticky.enable_display_excerpt', false);

        [$body, $postsQueries] = $this->listDiscussions();

        $this->assertCount(0, $postsQueries, "No posts query expected with excerpts disabled. Ran:\n".implode("\n", $postsQueries));

        foreach ($body['data'] as $discussion) {
            $this->assertArrayNotHasKey('firstPostExcerpt', $discussion['attributes']);
        }
    }
}
