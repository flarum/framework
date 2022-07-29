<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Statistics\Api\Controller;

use DateTime;
use DateTimeZone;
use Flarum\Discussion\Discussion;
use Flarum\Http\RequestUtil;
use Flarum\Post\Post;
use Flarum\Post\RegisteredTypesScope;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
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
    public static $lifetimeStatsCacheTtl = 300;

    /**
     * The amount of time to cache timed statistics data for in seconds.
     */
    public static $timedStatsCacheTtl = 900;

    protected $entities = [];

    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @var CacheRepository
     */
    protected $cache;

    public function __construct(SettingsRepositoryInterface $settings, CacheRepository $cache)
    {
        $this->settings = $settings;
        $this->cache = $cache;

        $this->entities = [
            'users' => [User::query(), 'joined_at'],
            'discussions' => [Discussion::query(), 'created_at'],
            'posts' => [Post::where('type', 'comment')->withoutGlobalScope(RegisteredTypesScope::class), 'created_at'],
        ];
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);

        // Must be an admin to get statistics data -- this is only visible on the admin
        // control panel.
        $actor->assertAdmin();

        $reportingPeriod = Arr::get($request->getQueryParams(), 'period');

        return new JsonResponse($this->getResponse($reportingPeriod));
    }

    private function getResponse(?string $period): array
    {
        if ($period === 'lifetime') {
            return $this->getLifetimeStatistics();
        }

        return array_merge(
            $this->getTimedStatistics(),
            ['timezoneOffset' => $this->getUserTimezone()->getOffset(new DateTime)]
        );
    }

    private function getLifetimeStatistics()
    {
        return $this->cache->remember('flarum-subscriptions.lifetime_stats', self::$lifetimeStatsCacheTtl, function () {
            return array_map(function ($entity) {
                return $entity[0]->count();
            }, $this->entities);
        });
    }

    private function getTimedStatistics()
    {
        return $this->cache->remember('flarum-subscriptions.timed_stats', self::$lifetimeStatsCacheTtl, function () {
            return array_map(function ($entity) {
                return $this->getTimedCounts($entity[0], $entity[1]);
            }, $this->entities);
        });
    }

    private function getTimedCounts(Builder $query, $column)
    {
        // Calculate the offset between the server timezone (which is used for
        // dates stored in the database) and the user's timezone (set via the
        // settings table). We will use this to make sure we aggregate the
        // daily/hourly statistics according to the user's timezone.
        $offset = $this->getTimezoneOffset();

        $results = $query
            ->selectRaw(
                'DATE_FORMAT(
                    @date := DATE_ADD('.$column.', INTERVAL ? SECOND), -- convert to user timezone
                    IF(@date > ?, \'%Y-%m-%d %H:00:00\', \'%Y-%m-%d\') -- if within the last 24 hours, group by hour
                ) as time_group',
                [$offset, new DateTime('-25 hours')]
            )
            ->selectRaw('COUNT(id) as count')
            ->where($column, '>', new DateTime('-365 days'))
            ->groupBy('time_group')
            ->pluck('count', 'time_group');

        // Now that we have the aggregated statistics, convert each time group
        // into a UNIX timestamp.
        $userTimezone = $this->getUserTimezone();

        $timed = [];

        $results->each(function ($count, $time) use (&$timed, $userTimezone) {
            $time = new DateTime($time, $userTimezone);
            $timed[$time->getTimestamp()] = (int) $count;
        });

        return $timed;
    }

    private function getTimezoneOffset()
    {
        $now = new DateTime;

        $dataTimezone = new DateTimeZone(date_default_timezone_get());

        return $this->getUserTimezone()->getOffset($now) - $dataTimezone->getOffset($now);
    }

    private function getUserTimezone()
    {
        return new DateTimeZone($this->settings->get('flarum-statistics.timezone', date_default_timezone_get()));
    }
}
