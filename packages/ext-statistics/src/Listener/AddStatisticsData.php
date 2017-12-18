<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Statistics\Listener;

use DateTime;
use DateTimeZone;
use Flarum\Core\Discussion;
use Flarum\Core\Post;
use Flarum\Core\User;
use Flarum\Event\ConfigureWebApp;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Events\Dispatcher;

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

    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(ConfigureWebApp::class, [$this, 'addStatisticsData']);
    }

    /**
     * @param ConfigureWebApp $event
     */
    public function addStatisticsData(ConfigureWebApp $event)
    {
        if ($event->isAdmin()) {
            $event->view->setVariable('statistics', array_merge(
                $this->getStatistics(),
                ['timezoneOffset' => $this->getUserTimezone()->getOffset(new DateTime)]
            ));
        }
    }

    private function getStatistics()
    {
        $entities = [
            'users' => [User::query(), 'join_time'],
            'discussions' => [Discussion::query(), 'start_time'],
            'posts' => [Post::where('type', 'comment'), 'time']
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
                    @date := DATE_ADD('.$column.', INTERVAL ? SECOND), -- correct for timezone
                    IF(@date > ?, \'%Y-%m-%dT%H:00:00\', \'%Y-%m-%dT00:00:00\') -- if within the last 48 hours, group by hour
                ) as time_group',
                [$offset, new DateTime('-48 hours')]
            )
            ->selectRaw('COUNT(id) as count')
            ->where($column, '>', new DateTime('-24 months'))
            ->groupBy('time_group')
            ->lists('count', 'time_group');

        // Now that we have the aggregated statistics, convert each point in
        // time into a UNIX timestamp .
        $displayTimezone = $this->getDisplayTimezone();
        $timed = [];

        $results->each(function ($count, $time) use (&$timed, $displayTimezone) {
            $time = new DateTime($time, $displayTimezone);
            $timed[$time->getTimestamp()] = $count;
        });

        return $timed;
    }

    private function getTimezoneOffset()
    {
        $dataTimezone = new DateTimeZone(date_default_timezone_get());

        return $this->getDisplayTimezone()->getOffset(new DateTime('now', $dataTimezone));
    }

    private function getUTCOffset()
    {
        $utcTimezone = new DateTimeZone('UTC');

        return $this->getDisplayTimezone()->getOffset(new DateTime('now', $utcTimezone));
    }

    private function getDisplayTimezone()
    {
        return new DateTimeZone($this->settings->get('flarum-statistics.timezone', date_default_timezone_get()));
    }

}
