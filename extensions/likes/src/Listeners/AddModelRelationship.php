<?php 
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Likes\Listeners;

use Flarum\Events\ModelRelationship;
use Flarum\Core\Posts\Post;
use Illuminate\Contracts\Events\Dispatcher;

class AddModelRelationship
{
    public function subscribe(Dispatcher $events)
    {
        $events->listen(ModelRelationship::class, [$this, 'addRelationship']);
    }

    public function addRelationship(ModelRelationship $event)
    {
        if ($event->model instanceof Post &&
            $event->relationship === 'likes') {
            return $event->model->belongsToMany('Flarum\Core\Users\User', 'posts_likes', 'post_id', 'user_id', 'likes');
        }
    }
}
