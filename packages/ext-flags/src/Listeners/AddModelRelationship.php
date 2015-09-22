<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Flags\Listeners;

use Flarum\Events\ModelRelationship;
use Flarum\Events\ModelDates;
use Flarum\Events\PostWasDeleted;
use Flarum\Core\Posts\Post;
use Flarum\Core\Users\User;
use Flarum\Flags\Flag;

class AddModelRelationship
{
    public function subscribe($events)
    {
        $events->listen(ModelRelationship::class, [$this, 'addFlagsRelationship']);
        $events->listen(ModelDates::class, [$this, 'modelDates']);
        $events->listen(PostWasDeleted::class, [$this, 'deleteFlags']);
    }

    public function addFlagsRelationship(ModelRelationship $event)
    {
        if ($event->model instanceof Post && $event->relationship === 'flags') {
            return $event->model->hasMany('Flarum\Flags\Flag', 'post_id');
        }
    }

    public function modelDates(ModelDates $event)
    {
        if ($event->model instanceof User) {
            $event->dates[] = 'flags_read_time';
        }
    }

    public function deleteFlags(PostWasDeleted $event)
    {
        $event->post->flags()->delete();
    }
}
