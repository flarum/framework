<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Statistics\Api\Controller;

use DateTime;
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
use Tobscure\JsonApi\Exception\InvalidParameterException;

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
            'posts' => [Post::where('type', 'comment')->withoutGlobalScope(RegisteredTypesScope::class), 'created_at']
        ];
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);

        // Must be an admin to get statistics data -- this is only visible on the admin
        // control panel.
        $actor->assertAdmin();

        $reportingPeriod = Arr::get($request->getQueryParams(), 'period');
        $model = Arr::get($request->getQueryParams(), 'model');

        return new JsonResponse($this->getResponse($model, $reportingPeriod));
    }

    private function getResponse(?string $model, ?string $period): array
    {
        if ($period === 'lifetime') {
            return $this->getLifetimeStatistics();
        }

        if (! Arr::exists($this->entities, $model)) {
            throw new InvalidParameterException();
        }

        return $this->getTimedStatistics($model);
    }

    private function getLifetimeStatistics()
    {
        return $this->cache->remember('flarum-subscriptions.lifetime_stats', self::$lifetimeStatsCacheTtl, function () {
            return array_map(function ($entity) {
                return $entity[0]->count();
            }, $this->entities);
        });
    }

    private function getTimedStatistics(string $model)
    {
        return $this->cache->remember("flarum-subscriptions.timed_stats.$model", self::$lifetimeStatsCacheTtl, function () use ($model) {
            return $this->getTimedCounts($this->entities[$model][0], $this->entities[$model][1]);
        });
    }

    private function getTimedCounts(Builder $query, $column)
    {
        $results = $query
            ->selectRaw(
                'DATE_FORMAT(
                    @date := '.$column.',
                    IF(@date > ?, \'%Y-%m-%d %H:00:00\', \'%Y-%m-%d\') -- if within the last 24 hours, group by hour
                ) as time_group',
                [new DateTime('-25 hours')]
            )
            ->selectRaw('COUNT(id) as count')
            ->where($column, '>', new DateTime('-365 days'))
            ->groupBy('time_group')
            ->pluck('count', 'time_group');

        $timed = [];

        $results->each(function ($count, $time) use (&$timed) {
            $time = new DateTime($time);
            $timed[$time->getTimestamp()] = (int) $count;
        });

        return $timed;
    }
}
