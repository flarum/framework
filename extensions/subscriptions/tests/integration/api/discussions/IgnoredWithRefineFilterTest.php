<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Subscriptions\Tests\integration\api\discussions;

use Flarum\Discussion\Discussion;
use Flarum\Discussion\Search\DiscussionSearcher;
use Flarum\Extend;
use Flarum\Search\Database\DatabaseSearchDriver;
use Flarum\Subscriptions\Tests\fixtures\WithoutDiscussionsFilter;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\Test;

/**
 * A refine filter (`without`) narrows the list a person is looking at, as a
 * date range would. All Discussions refined is still All Discussions, so it
 * still leaves out the discussions they ignore.
 */
class IgnoredWithRefineFilterTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-subscriptions');

        $this->extend(
            (new Extend\SearchDriver(DatabaseSearchDriver::class))
                ->addFilter(DiscussionSearcher::class, WithoutDiscussionsFilter::class)
        );

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Normal', 'created_at' => '2026-01-02 10:00:00', 'last_posted_at' => '2026-01-02 10:00:00', 'user_id' => 1, 'comment_count' => 1],
                ['id' => 2, 'title' => 'Also normal', 'created_at' => '2026-01-02 11:00:00', 'last_posted_at' => '2026-01-02 11:00:00', 'user_id' => 1, 'comment_count' => 1],
                ['id' => 3, 'title' => 'Ignored', 'created_at' => '2026-01-02 12:00:00', 'last_posted_at' => '2026-01-02 12:00:00', 'user_id' => 1, 'comment_count' => 1],
            ],
            'discussion_user' => [
                ['discussion_id' => 3, 'user_id' => 2, 'subscription' => 'ignore'],
            ],
        ]);
    }

    private function ids(array $filter): array
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions', ['authenticatedAs' => 2])->withQueryParams(['filter' => $filter])
        );

        $body = $response->getBody()->getContents();

        $this->assertEquals(200, $response->getStatusCode(), $body);

        return array_map('intval', Arr::pluck(json_decode($body, true)['data'], 'id'));
    }

    #[Test]
    public function all_discussions_refined_still_leaves_out_ignored_discussions()
    {
        $this->assertEquals([2], $this->ids(['without' => '1']));
    }

    #[Test]
    public function the_ignoring_list_refined_still_lists_them()
    {
        $this->assertEquals([3], $this->ids(['subscription' => 'ignoring', 'without' => '1']));
    }
}
