<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Statistics\tests\integration\api;

use Carbon\Carbon;
use Flarum\Statistics\Extend\Statistics;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use PHPUnit\Framework\Attributes\Test;

/**
 * Other extensions add their own statistics, which show alongside the
 * built-in ones.
 */
class ExtendStatisticsTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected Carbon $joined;

    protected function setUp(): void
    {
        parent::setUp();

        $this->joined = Carbon::now()->subDays(10)->startOfDay()->addHours(12);

        $this->extension('flarum-statistics');

        $this->extend(
            (new Statistics())
                ->entity('confirmed_users', fn () => User::query()->where('is_email_confirmed', true), 'joined_at')
        );

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'confirmed', 'email' => 'confirmed@machine.local', 'is_email_confirmed' => 1, 'joined_at' => $this->joined],
                ['id' => 4, 'username' => 'unconfirmed', 'email' => 'unconfirmed@machine.local', 'is_email_confirmed' => 0, 'joined_at' => $this->joined],
            ],
        ]);
    }

    private function statistics(array $params): array
    {
        $response = $this->send($this->request('GET', '/api/statistics', ['authenticatedAs' => 1])->withQueryParams($params));

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());

        return json_decode((string) $response->getBody(), true);
    }

    #[Test]
    public function an_added_statistic_is_counted_in_the_totals(): void
    {
        $totals = $this->statistics(['period' => 'lifetime']);

        $this->assertSame(['users', 'discussions', 'posts', 'confirmed_users'], array_keys($totals));
        $this->assertSame(User::query()->where('is_email_confirmed', true)->count(), $totals['confirmed_users']);
    }

    #[Test]
    public function an_added_statistic_is_counted_over_time_with_its_own_query(): void
    {
        $timed = $this->statistics(['model' => 'confirmed_users']);

        // The unconfirmed user joined the same day, and isn't counted.
        $this->assertSame(1, $timed[$this->joined->copy()->startOfDay()->getTimestamp()] ?? null);
    }

    #[Test]
    public function the_forum_tells_admins_which_statistics_there_are(): void
    {
        $forum = fn (int $as) => json_decode((string) $this->send($this->request('GET', '/api', ['authenticatedAs' => $as]))->getBody(), true)['data']['attributes'];

        $this->assertSame(['users', 'discussions', 'posts', 'confirmed_users'], $forum(1)['statisticsEntities']);
        $this->assertArrayNotHasKey('statisticsEntities', $forum(2));
    }

    #[Test]
    public function statistics_are_cached_under_their_own_name(): void
    {
        $this->statistics(['period' => 'lifetime']);
        $this->statistics(['model' => 'users']);

        $cache = $this->app()->getContainer()->make(CacheRepository::class);

        $this->assertTrue($cache->has('flarum-statistics.lifetime_stats'));
        $this->assertTrue($cache->has('flarum-statistics.timed_stats.users'));
    }
}
