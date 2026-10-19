<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Statistics\tests\integration\api;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Timed statistics come back by the hour for the last day, so today's chart
 * has a shape, and by the day before that.
 */
class TimedStatisticsGroupingTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected Carbon $now;

    protected function setUp(): void
    {
        parent::setUp();

        $this->now = Carbon::now();

        $this->extension('flarum-statistics');

        $discussion = fn (int $id, Carbon $at) => ['id' => $id, 'title' => __CLASS__, 'created_at' => $at, 'last_posted_at' => $at, 'user_id' => 1, 'comment_count' => 1];

        $this->prepareDatabase([
            Discussion::class => [
                // Two in the same hour, two hours ago.
                $discussion(1, $this->now->copy()->subHours(2)->startOfHour()->addMinutes(5)),
                $discussion(2, $this->now->copy()->subHours(2)->startOfHour()->addMinutes(35)),
                // One five hours ago.
                $discussion(3, $this->now->copy()->subHours(5)),
                // Two on the same day, three days ago.
                $discussion(4, $this->now->copy()->subDays(3)->startOfDay()->addHours(3)),
                $discussion(5, $this->now->copy()->subDays(3)->startOfDay()->addHours(20)),
            ],
        ]);
    }

    #[Test]
    public function the_last_day_is_counted_by_the_hour_and_earlier_days_by_the_day(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/statistics', ['authenticatedAs' => 1])->withQueryParams(['model' => 'discussions'])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEqualsCanonicalizing([
            $this->now->copy()->subHours(2)->startOfHour()->getTimestamp() => 2,
            $this->now->copy()->subHours(5)->startOfHour()->getTimestamp() => 1,
            $this->now->copy()->subDays(3)->startOfDay()->getTimestamp() => 2,
        ], json_decode((string) $response->getBody(), true));
    }

    #[Test]
    public function a_custom_range_in_the_past_is_counted_by_the_day(): void
    {
        $start = $this->now->copy()->subDays(4)->startOfDay();
        $end = $this->now->copy()->subDays(2)->startOfDay();

        $response = $this->send(
            $this->request('GET', '/api/statistics', ['authenticatedAs' => 1])->withQueryParams([
                'model' => 'discussions',
                'period' => 'custom',
                'dateRange' => ['start' => $start->getTimestamp(), 'end' => $end->getTimestamp()],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEqualsCanonicalizing([
            $this->now->copy()->subDays(3)->startOfDay()->getTimestamp() => 2,
        ], json_decode((string) $response->getBody(), true));
    }
}
