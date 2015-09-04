<?php 
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tags\Listeners;

use Flarum\Events\ModelRelationship;
use Flarum\Core\Discussions\Discussion;
use Flarum\Tags\Tag;

class AddModelRelationship
{
    public function subscribe($events)
    {
        $events->listen(ModelRelationship::class, [$this, 'addTagsRelationship']);
    }

    public function addTagsRelationship(ModelRelationship $event)
    {
        if ($event->model instanceof Discussion &&
            $event->relationship === 'tags') {
            return $event->model->belongsToMany('Flarum\Tags\Tag', 'discussions_tags', null, null, 'tags');
        }
    }
}
