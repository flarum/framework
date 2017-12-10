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
use Flarum\Core\Discussion;
use Flarum\Core\Post;
use Flarum\Core\User;
use Flarum\Event\ConfigureWebApp;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Events\Dispatcher;

class AddStatisticsData
{
    public function subscribe(Dispatcher $events)
    {
        $events->listen(ConfigureWebApp::class, [$this, 'addStatisticsData']);
    }

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
        return $query
            ->selectRaw('DATE('.$column.') as date')
            ->selectRaw('COUNT(id) as count')
            ->where($column, '>', new DateTime('-24 months'))
            ->groupBy('date')
            ->lists('count', 'date');
    }
}
