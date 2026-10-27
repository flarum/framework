<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api;

use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * Listing endpoints count every match for `meta.page.total`, which can cost far
 * more than the page itself. `page[total]=0` skips the count.
 */
class PaginationTotalTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'One', 'created_at' => '2026-01-01 10:00:00', 'last_posted_at' => '2026-01-01 10:00:00', 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1],
                ['id' => 2, 'title' => 'Two', 'created_at' => '2026-01-02 10:00:00', 'last_posted_at' => '2026-01-02 10:00:00', 'user_id' => 1, 'first_post_id' => 2, 'comment_count' => 1],
                ['id' => 3, 'title' => 'Three', 'created_at' => '2026-01-03 10:00:00', 'last_posted_at' => '2026-01-03 10:00:00', 'user_id' => 1, 'first_post_id' => 3, 'comment_count' => 1],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'number' => 1, 'created_at' => '2026-01-01 10:00:00', 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>One</p></t>'],
                ['id' => 2, 'discussion_id' => 2, 'number' => 1, 'created_at' => '2026-01-02 10:00:00', 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Two</p></t>'],
                ['id' => 3, 'discussion_id' => 3, 'number' => 1, 'created_at' => '2026-01-03 10:00:00', 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Three</p></t>'],
            ],
        ]);
    }

    #[Test]
    public function totals_are_included_by_default(): void
    {
        $body = $this->list(['limit' => 2]);

        $this->assertSame(3, $body['meta']['page']['total']);
        $this->assertArrayHasKey('next', $body['links']);
    }

    #[Test]
    public function page_total_zero_skips_the_count(): void
    {
        $db = $this->database();
        $db->enableQueryLog();

        $body = $this->list(['limit' => 2, 'total' => '0']);

        $counts = array_filter($db->getQueryLog(), fn (array $query) => str_contains($query['query'], 'count(*)') && str_contains($query['query'], '`discussions`'));

        $this->assertArrayNotHasKey('total', $body['meta']['page'] ?? []);
        $this->assertSame([], array_values($counts));
        $this->assertCount(2, $body['data']);
        $this->assertArrayHasKey('next', $body['links'], 'A full page still links to the next one.');
    }

    #[Test]
    public function without_a_total_a_partial_page_has_no_next_link(): void
    {
        $body = $this->list(['limit' => 5, 'total' => 'false']);

        $this->assertCount(3, $body['data']);
        $this->assertArrayNotHasKey('next', $body['links'] ?? []);
    }

    protected function list(array $page): array
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions', ['authenticatedAs' => 2])->withQueryParams(['page' => $page])
        );

        $body = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode(), $body);

        return json_decode($body, true);
    }
}
