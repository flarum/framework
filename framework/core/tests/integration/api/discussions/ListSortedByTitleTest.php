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
use PHPUnit\Framework\Attributes\Test;

/**
 * Sorting discussions by title, exposed as the `az` and `za` aliases.
 *
 * How text orders is the database's decision, not ours, and the four supported
 * backends do not agree on it. MySQL, MariaDB and PostgreSQL fold case and
 * accents through the column's collation, so `Apple`, `apple` and `Ápple` sort
 * together. SQLite compares raw bytes, which puts every capitalised title ahead
 * of every lowercase one, and accented titles after Z.
 *
 * These tests assert only what holds everywhere: that the sort exists, that it
 * orders titles that differ in their first letter, and that descending is the
 * reverse of ascending. Case and accent handling is deliberately left to the
 * database and documented rather than asserted, since a test that passed on
 * SQLite would have to contradict one that passed on MySQL.
 */
class ListSortedByTitleTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            Discussion::class => [
                // Titles chosen to differ in their first letter and to share a
                // case, so that the expected order is the same on every backend.
                $this->discussion(1, 'banana'),
                $this->discussion(2, 'apricot'),
                $this->discussion(3, 'cherry'),
                $this->discussion(4, 'damson'),
            ],
            User::class => [
                $this->normalUser(),
            ],
        ]);
    }

    protected function discussion(int $id, string $title): array
    {
        return [
            'id' => $id,
            'title' => $title,
            'created_at' => Carbon::createFromDate(2020, 1, $id)->toDateTimeString(),
            'last_posted_at' => Carbon::createFromDate(2020, 1, $id)->toDateTimeString(),
            'user_id' => 1,
            'comment_count' => 1,
        ];
    }

    /**
     * @return string[] The titles returned, in the order the API returned them.
     */
    protected function titles(string $sort): array
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions')
                ->withQueryParams(['sort' => $sort])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);

        return array_map(fn ($d) => $d['attributes']['title'], $body['data']);
    }

    #[Test]
    public function discussions_can_be_sorted_by_title_ascending()
    {
        $this->assertEquals(
            ['apricot', 'banana', 'cherry', 'damson'],
            $this->titles('title')
        );
    }

    #[Test]
    public function discussions_can_be_sorted_by_title_descending()
    {
        $this->assertEquals(
            ['damson', 'cherry', 'banana', 'apricot'],
            $this->titles('-title')
        );
    }

    #[Test]
    public function the_aliases_are_published_for_the_frontend()
    {
        // `az` and `za` are what appear in the URL and in the sort dropdown.
        // They are translated to the real sort before the API is called, so
        // the API itself never sees them — what matters is that the resource
        // publishes the mapping.
        $sortMap = $this->app()->getContainer()
            ->make(\Flarum\Api\Resource\DiscussionResource::class)
            ->sortMap();

        $this->assertArrayHasKey('az', $sortMap);
        $this->assertArrayHasKey('za', $sortMap);
        $this->assertEquals('title', $sortMap['az']);
        $this->assertEquals('-title', $sortMap['za']);
    }

    #[Test]
    public function descending_is_the_reverse_of_ascending()
    {
        $this->assertEquals(
            array_reverse($this->titles('title')),
            $this->titles('-title')
        );
    }

    #[Test]
    public function an_unknown_sort_is_rejected_rather_than_ignored()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions')
                ->withQueryParams(['sort' => 'nonsense'])
        );

        $this->assertEquals(400, $response->getStatusCode());
    }
}
