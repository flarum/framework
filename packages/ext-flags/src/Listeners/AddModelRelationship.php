<?php 
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Reports\Listeners;

use Flarum\Events\ModelRelationship;
use Flarum\Events\ModelDates;
use Flarum\Core\Posts\Post;
use Flarum\Core\Users\User;
use Flarum\Reports\Report;

class AddModelRelationship
{
    public function subscribe($events)
    {
        $events->listen(ModelRelationship::class, [$this, 'addReportsRelationship']);
        $events->listen(ModelDates::class, [$this, 'modelDates']);
    }

    public function addReportsRelationship(ModelRelationship $event)
    {
        if ($event->model instanceof Post && $event->relationship === 'reports') {
            return $event->model->hasMany('Flarum\Reports\Report', 'post_id');
        }
    }

    public function modelDates(ModelDates $event)
    {
        if ($event->model instanceof User) {
            $event->dates[] = 'reports_read_time';
        }
    }
}
