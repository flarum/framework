<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Statistics\Api\Controller;

use Carbon\Carbon;
use DateTime;
use Exception;
use Flarum\Http\Exception\InvalidParameterException;
use Flarum\Http\RequestUtil;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ShowStatisticsData implements RequestHandlerInterface
{
    /**
     * The amount of time to cache lifetime statistics data for in seconds.
     */
    public static int $lifetimeStatsCacheTtl = 300;

    /**
     * The amount of time to cache timed statistics data for in seconds.
     */
    public static int $timedStatsCacheTtl = 900;

    /**
     * The statistics registered with the Statistics extender, by name.
     *
     * @var array<string, array{query: callable(): Builder, column: string}>
     */
    protected array $entities = [];

    public function __construct(
        protected SettingsRepositoryInterface $settings,
        protected CacheRepository $cache,
        Container $container
    ) {
        $this->entities = $container->bound('flarum-statistics.entities') ? $container->make('flarum-statistics.entities') : [];
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);

        // Must be an admin to get statistics data -- this is only visible on the admin
        // control panel.
        $actor->assertAdmin();

        $query = $request->getQueryParams();

        $reportingPeriod = Arr::get($query, 'period');
        $model = Arr::get($query, 'model');
        $customDateRange = Arr::get($query, 'dateRange');

        return new JsonResponse($this->getResponse($model, $reportingPeriod, $customDateRange));
    }

    private function getResponse(?string $model, ?string $period, ?array $customDateRange): array
    {
        if ($period === 'lifetime') {
            return $this->getLifetimeStatistics();
        }

        if (! Arr::exists($this->entities, $model)) {
            throw new InvalidParameterException('A model must be specified');
        }

        if ($period === 'custom') {
            $start = (int) $customDateRange['start'];
            $end = (int) $customDateRange['end'];

            if (! $customDateRange || ! $start || ! $end) {
                throw new InvalidParameterException('A custom date range must be specified');
            }

            // Seconds-based timestamps
            $startRange = Carbon::createFromTimestampUTC($start)->toDateTime();
            $endRange = Carbon::createFromTimestampUTC($end)->toDateTime();

            // We can't really cache this
            return $this->getTimedCounts($this->query($model), $this->entities[$model]['column'], $startRange, $endRange);
        }

        return $this->getTimedStatistics($model);
    }

    /**
     * A fresh query for one statistic's records.
     */
    private function query(string $model): Builder
    {
        return ($this->entities[$model]['query'])();
    }

    private function getLifetimeStatistics(): array
    {
        return $this->cache->remember('flarum-statistics.lifetime_stats', self::$lifetimeStatsCacheTtl, function () {
            return array_map(fn (string $model) => $this->query($model)->count(), array_combine(array_keys($this->entities), array_keys($this->entities)));
        });
    }

    private function getTimedStatistics(string $model): array
    {
        return $this->cache->remember("flarum-statistics.timed_stats.$model", self::$timedStatsCacheTtl, function () use ($model) {
            return $this->getTimedCounts($this->query($model), $this->entities[$model]['column']);
        });
    }

    /**
     * Counts by the hour for the last day, so today has a shape, and by the
     * day before that.
     *
     * These are two queries rather than one that decides, row by row, which
     * of the two to format each date as: grouping by the day alone is far
     * cheaper to compute over a large table, and the last day holds few rows.
     *
     * @return array<int, int> The number of records, keyed by the timestamp of each hour or day.
     */
    private function getTimedCounts(Builder $query, string $column, ?DateTime $startDate = null, ?DateTime $endDate = null): array
    {
        $diff = $startDate && $endDate ? $startDate->diff($endDate) : null;

        if (! isset($startDate)) {
            // need -12 months and period before that
            $startDate = new DateTime('-2 years');
        } else {
            // If the start date is custom, we need to include an equal amount beforehand
            // to show the data for the previous period.
            $startDate = (new Carbon($startDate))->subtract($diff)->toDateTime();
        }

        if (! isset($endDate)) {
            $endDate = new DateTime();
        }

        $hourlyFrom = new DateTime('-25 hours');

        $wrapped = $query->getQuery()->getGrammar()->wrap($column);

        $byHour = match ($query->getConnection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m-%d %H:00:00', $wrapped)",
            'pgsql' => "TO_CHAR($wrapped, 'YYYY-MM-DD HH24:00:00')",
            'mysql', 'mariadb' => "DATE_FORMAT($wrapped, '%Y-%m-%d %H:00:00')",
            default => throw new Exception('Unsupported database driver'),
        };

        $timed = [];

        $count = function (Builder $query, string $group, DateTime $from, DateTime $to) use ($column, &$timed) {
            $query
                ->selectRaw($group.' as time_group')
                ->selectRaw('COUNT(id) as count')
                ->where($column, '>', $from)
                ->where($column, '<=', $to)
                ->groupBy('time_group')
                ->pluck('count', 'time_group')
                ->each(function ($count, $time) use (&$timed) {
                    $time = (new DateTime($time))->getTimestamp();

                    // The day the last 24 hours begin on is counted in both
                    // queries, and its midnight hour shares the day's key.
                    $timed[$time] = ($timed[$time] ?? 0) + (int) $count;
                });
        };

        // DATE() is understood by every supported database.
        $count(clone $query, "DATE($wrapped)", $startDate, min($endDate, $hourlyFrom));

        if ($endDate > $hourlyFrom) {
            $count(clone $query, $byHour, max($startDate, $hourlyFrom), $endDate);
        }

        return $timed;
    }
}
