<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Statistics;

use DateTime;
use DateTimeZone;
use Flarum\Discussion\Discussion;
use Flarum\Frontend\Document;
use Flarum\Post\Post;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

class AddStatisticsData
{
    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @param SettingsRepositoryInterface $settings
     */
    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
    }

    public function __invoke(Document $view)
    {
        $view->payload['statistics'] = array_merge(
            $this->getStatistics(),
            ['timezoneOffset' => $this->getUserTimezone()->getOffset(new DateTime)]
        );
    }

    private function getStatistics()
    {
        $entities = [
            'users' => [User::query(), 'joined_at'],
            'discussions' => [Discussion::query(), 'created_at'],
            'posts' => [Post::where('type', 'comment'), 'created_at']
        ];

        return array_map(function ($entity) {
            return [
                'total' => $entity[0]->count(),
                'timed' => $this->getTimedCounts($entity[0], $entity[1])
            ];
        }, $entities);
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
                    IF(@date > ?, \'%Y-%m-%d %H:00:00\', \'%Y-%m-%d\') -- if within the last 48 hours, group by hour
                ) as time_group',
                [$offset, new DateTime('-48 hours')]
            )
            ->selectRaw('COUNT(id) as count')
            ->where($column, '>', new DateTime('-24 months'))
            ->groupBy('time_group')
            ->pluck('count', 'time_group');

        // Now that we have the aggregated statistics, convert each time group
        // into a UNIX timestamp.
        $userTimezone = $this->getUserTimezone();

        $timed = [];

        $results->each(function ($count, $time) use (&$timed, $userTimezone) {
            $time = new DateTime($time, $userTimezone);
            $timed[$time->getTimestamp()] = $count;
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
