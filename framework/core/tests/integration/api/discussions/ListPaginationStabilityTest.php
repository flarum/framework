<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\discussions;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * Results are paginated by offset, so the order has to be total. Sorting by a column that
 * isn't unique leaves tied rows in whatever order the database happens to return, which
 * differs between engines and versions, and can differ between two executions of the same
 * query. When it does, a row can appear on one page and again on the next while another is
 * never returned at all.
 */
class ListPaginationStabilityTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * Enough discussions to span several pages, deliberately tied on every sortable column:
     * one `last_posted_at`, one `comment_count` and one `title` shared by all of them.
     */
    protected const COUNT = 24;

    protected function setUp(): void
    {
        parent::setUp();

        $tied = Carbon::parse('2026-01-01 12:00:00')->toDateTimeString();

        $discussions = [];

        for ($id = 1; $id <= self::COUNT; $id++) {
            $discussions[] = [
                'id' => $id,
                'title' => 'Tied title',
                'created_at' => $tied,
                'last_posted_at' => $tied,
                'user_id' => 1,
                'first_post_id' => 0,
                'comment_count' => 1,
                'is_private' => 0,
            ];
        }

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
            Discussion::class => $discussions,
        ]);
    }

    /**
     * @return list<int>
     */
    private function idsOnPage(int $offset, int $limit, ?string $sort): array
    {
        $params = ['page' => ['offset' => $offset, 'limit' => $limit]];

        if ($sort !== null) {
            $params['sort'] = $sort;
        }

        $response = $this->send(
            $this->request('GET', '/api/discussions', ['authenticatedAs' => 1])
                ->withQueryParams($params)
        );

        $this->assertEquals(200, $response->getStatusCode());

        return array_map('intval', Arr::pluck(
            json_decode($response->getBody()->getContents(), true)['data'],
            'id'
        ));
    }

    #[Test]
    #[TestWith([null], 'default sort')]
    #[TestWith(['-lastPostedAt'], 'lastPostedAt descending')]
    #[TestWith(['lastPostedAt'], 'lastPostedAt ascending')]
    #[TestWith(['-commentCount'], 'commentCount descending')]
    #[TestWith(['createdAt'], 'createdAt ascending')]
    #[TestWith(['title'], 'title ascending')]
    public function paging_through_a_tied_sort_returns_every_discussion_exactly_once(?string $sort): void
    {
        $limit = 5;
        $seen = [];

        for ($offset = 0; $offset < self::COUNT; $offset += $limit) {
            $seen = array_merge($seen, $this->idsOnPage($offset, $limit, $sort));
        }

        $duplicates = array_keys(array_filter(array_count_values($seen), fn ($n) => $n > 1));
        $missing = array_values(array_diff(range(1, self::COUNT), $seen));

        $this->assertSame([], $duplicates, 'No discussion should appear on more than one page');
        $this->assertSame([], $missing, 'Every discussion should appear on some page');
        $this->assertCount(self::COUNT, $seen);
    }

    /**
     * The order also has to be the same each time it is asked for, or paging through it is
     * unsound however complete any single pass looks.
     */
    #[Test]
    public function the_order_is_repeatable(): void
    {
        $first = $this->idsOnPage(0, self::COUNT, '-lastPostedAt');
        $second = $this->idsOnPage(0, self::COUNT, '-lastPostedAt');

        $this->assertSame($first, $second);
    }

    /**
     * With every sortable column tied, the primary key is what is left to order by. It
     * follows the direction of the sort it is breaking ties for, so that an index on the
     * sorted column still answers the ordering.
     */
    #[Test]
    public function ties_are_ordered_by_primary_key(): void
    {
        $this->assertSame(
            array_reverse(range(1, self::COUNT)),
            $this->idsOnPage(0, self::COUNT, '-lastPostedAt'),
            'A descending sort should break ties descending'
        );

        $this->assertSame(
            range(1, self::COUNT),
            $this->idsOnPage(0, self::COUNT, 'lastPostedAt'),
            'An ascending sort should break ties ascending'
        );
    }
}
