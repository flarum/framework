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

    /**
     * Follows one paging hint until there is none.
     *
     * @param array<string, string> $auth
     * @param array<string, mixed> $arguments
     * @return array{list<list<int>>, array<string, mixed>} the pages' post numbers in reading order, and the arguments of the page the walk ended on
     */
    protected function walk(array $auth, array $arguments, string $direction): array
    {
        $pages = [];
        $limit = $arguments['limit'];

        for ($i = 0; $i < 20; $i++) {
            $output = $this->toolOutput($this->callTool('read_discussion', ['id' => '1', ...$arguments], $auth));
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
