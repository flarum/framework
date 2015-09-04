<?php 
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Mentions\Listeners;

use Flarum\Events\ModelRelationship;
use Flarum\Core\Posts\Post;
use Flarum\Core\Users\User;

class AddModelRelationships
{
    public function subscribe($events)
    {
        $events->listen(ModelRelationship::class, [$this, 'addRelationships']);
    }

    public function addRelationships(ModelRelationship $event)
    {
        if ($event->model instanceof Post) {
            if ($event->relationship === 'mentionedBy') {
                return $event->model->belongsToMany(Post::class, 'mentions_posts', 'mentions_id', 'post_id', 'mentionedBy');
            }

            if ($event->relationship === 'mentionsPosts') {
                return $event->model->belongsToMany(Post::class, 'mentions_posts', 'post_id', 'mentions_id', 'mentionsPosts');
            }

            if ($event->relationship === 'mentionsUsers') {
                return $event->model->belongsToMany(User::class, 'mentions_users', 'post_id', 'mentions_id', 'mentionsUsers');
            }
        }
    }
}
