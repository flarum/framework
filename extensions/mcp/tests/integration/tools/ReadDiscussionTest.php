<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tests\integration\tools;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Mcp\Tests\integration\McpTestCase;
use Flarum\Post\Post;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class ReadDiscussionTest extends McpTestCase
{
    /**
     * Post numbers, with a gap where a post was deleted.
     */
    protected const array NUMBERS = [1, 2, 3, 5, 6, 7, 8];

    protected function setUp(): void
    {
        parent::setUp();

        $posts = [];
        foreach (static::NUMBERS as $i => $number) {
            $posts[] = [
                'id' => $i + 1,
                'discussion_id' => 1,
                'number' => $number,
                'user_id' => 2,
                'type' => 'comment',
                'content' => "<t><p>Post $number</p></t>",
                'created_at' => Carbon::now()->subHours(10 - $i),
            ];
        }

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
            Discussion::class => [
                ['id' => 1, 'title' => 'Long', 'slug' => 'long', 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => count($posts), 'last_post_number' => 8, 'created_at' => Carbon::now()->subDay()],
            ],
            Post::class => $posts,
        ]);
    }

    /**
     * @return array<string, array{int}>
     */
    public static function limits(): array
    {
        return ['limit 1' => [1], 'limit 3' => [3]];
    }

    #[Test]
    #[DataProvider('limits')]
    public function walking_next_and_previous_visits_every_post_once_in_order(int $limit): void
    {
        $auth = $this->bearer($this->developerToken(2));

        [$forwards, $last] = $this->walk($auth, ['limit' => $limit], 'next');
        $this->assertSame(static::NUMBERS, array_merge(...$forwards));

        [$backwards] = $this->walk($auth, $last, 'previous');
        $this->assertSame(static::NUMBERS, array_merge(...$backwards));
    }

    #[Test]
    public function near_is_an_entry_point_from_which_paging_continues(): void
    {
        $auth = $this->bearer($this->developerToken(2));

        [$forwards] = $this->walk($auth, ['limit' => 2, 'near' => 6], 'next');
        [$backwards] = $this->walk($auth, ['limit' => 2, 'near' => 6], 'previous');

        // Both walks start on the page around post 6.
        $this->assertContains(6, $forwards[0]);
        $this->assertSame($forwards[0], $backwards[count($backwards) - 1]);

        $this->assertSame(static::NUMBERS, array_merge(...array_slice($backwards, 0, -1), ...$forwards));
    }

    #[Test]
    public function walking_previous_over_pages_cut_to_size_visits_every_post_once(): void
    {
        $count = 60;
        $this->longDiscussion($count);

        $auth = $this->bearer($this->developerToken(2));

        [$forwards, $last] = $this->walk($auth, ['limit' => 50], 'next', '2');
        $this->assertGreaterThan(2, count($forwards), 'pages are cut to size');
        $this->assertSame(range(1, $count), array_merge(...$forwards));

        [$backwards] = $this->walk($auth, $last, 'previous', '2');
        $this->assertSame(range(1, $count), array_merge(...$backwards));
    }

    #[Test]
    public function the_discussion_resource_is_read_in_full_by_following_its_paging_uris(): void
    {
        $count = 60;
        $this->longDiscussion($count);

        $auth = $this->bearer($this->developerToken(2));

        [$forwards, $last] = $this->walkResource($auth, 'flarum://discussions/2', 'next');
        $this->assertGreaterThan(1, count($forwards));
        $this->assertSame(range(1, $count), array_merge(...$forwards));

        [$backwards] = $this->walkResource($auth, $last, 'previous');
        $this->assertSame(range(1, $count), array_merge(...array_reverse($backwards)));
    }

    /**
     * Discussion 2, with posts of about 3,900 characters each: 25 of them
     * exceed the payload cap.
     */
    protected function longDiscussion(int $count): void
    {
        $posts = [];
        for ($number = 1; $number <= $count; $number++) {
            $posts[] = [
                'id' => 100 + $number,
                'discussion_id' => 2,
                'number' => $number,
                'user_id' => 2,
                'type' => 'comment',
                'content' => '<t><p>'.$number.' '.str_repeat('lorem ', 650).'</p></t>',
                'created_at' => Carbon::now()->subMinutes(100 - $number),
            ];
        }

        $this->prepareDatabase([
            Discussion::class => [
                ['id' => 2, 'title' => 'Longer', 'slug' => 'longer', 'user_id' => 2, 'first_post_id' => 101, 'comment_count' => $count, 'last_post_number' => $count, 'created_at' => Carbon::now()->subDay()],
            ],
            Post::class => $posts,
        ]);
    }

    /**
     * Reads a resource and follows one paging URI until there is none.
     *
     * @param array<string, string> $auth
     * @return array{list<list<int>>, string} the pages' post numbers in the order read, and the URI of the last page
     */
    protected function walkResource(array $auth, string $uri, string $direction): array
    {
        $pages = [];

        for ($i = 0; $i < 20; $i++) {
            $read = $this->rpcResult($this->modern('resources/read', ['uri' => $uri], $auth, $uri));
            $this->assertSame($uri, $read['contents'][0]['uri']);
            $output = json_decode($read['contents'][0]['text'], true);
            $pages[] = array_column($output['posts'], 'number');

            $hint = $output['paging'][$direction] ?? null;
            if (! $hint) {
                break;
            }

            $this->assertIsString($hint['uri'] ?? null, "paging.$direction gives the URI to read: ".json_encode($hint));
            $uri = $hint['uri'];
        }

        return [$pages, $uri];
    }

    /**
     * Follows one paging hint until there is none.
     *
     * @param array<string, string> $auth
     * @param array<string, mixed> $arguments
     * @return array{list<list<int>>, array<string, mixed>} the pages' post numbers in reading order, and the arguments of the page the walk ended on
     */
    protected function walk(array $auth, array $arguments, string $direction, string $id = '1'): array
    {
        $pages = [];
        $limit = $arguments['limit'];

        for ($i = 0; $i < 20; $i++) {
            $output = $this->toolOutput($this->callTool('read_discussion', ['id' => $id, ...$arguments], $auth));
            $pages[] = array_column($output['posts'], 'number');

            $hint = $output['paging'][$direction] ?? null;
            if (! $hint) {
                break;
            }

            $arguments = ['limit' => $limit, ...$hint];
        }

        return [$direction === 'previous' ? array_reverse($pages) : $pages, $arguments];
    }
}
