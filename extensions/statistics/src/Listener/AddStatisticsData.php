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
        $event->view->setVariable('statistics', $this->getStatistics());
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
                'daily' => $this->getDailyCounts($entity[0], $entity[1])
            ];
        }, $entities);
    }

    private function getDailyCounts(Builder $query, $column)
    {
        // Calculate the offset between the server timezone (which is used for
        // dates stored in the database) and the user's timezone (set via the
        // settings table). We will use this to adjust dates before aggregating
        // daily/hourly statistics.
        $offset = $this->getTimezoneOffset();

        return $query
            ->selectRaw(
                'UNIX_TIMESTAMP(
                    DATE_FORMAT(
                        @date := DATE_ADD('.$column.', INTERVAL ? SECOND), -- correct for timezone
                        IF(@date > ?, \'%Y-%m-%d %H:00:00\', \'%Y-%m-%d\') -- if within the last 48 hours, group by hour
                    )
                ) as period',
                [$offset, new DateTime('-48 hours')]
            )
            ->selectRaw('COUNT(id) as count')
            ->where($column, '>', new DateTime('-24 months'))
            ->groupBy('period')
            ->lists('count', 'period');
    }

    private function getTimezoneOffset()
    {
        $dataTimezone = new DateTimeZone(date_default_timezone_get());
        $displayTimezone = new DateTimeZone($this->settings->get('flarum-statistics.timezone', date_default_timezone_get()));

        return $displayTimezone->getOffset(new DateTime('now', $dataTimezone));
    }
}
