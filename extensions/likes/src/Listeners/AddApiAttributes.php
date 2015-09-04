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

use Flarum\Events\ApiAttributes;
use Flarum\Events\ApiRelationship;
use Flarum\Events\BuildApiAction;
use Illuminate\Contracts\Events\Dispatcher;
use Flarum\Api\Serializers\PostSerializer;
use Flarum\Api\Actions\Discussions;
use Flarum\Api\Actions\Posts;

class AddApiAttributes
{
    public function subscribe(Dispatcher $events)
    {
        $events->listen(ApiAttributes::class, [$this, 'addAttributes']);
        $events->listen(ApiRelationship::class, [$this, 'addRelationship']);
        $events->listen(BuildApiAction::class, [$this, 'includeLikes']);
    }

    public function addAttributes(ApiAttributes $event)
    {
        if ($event->serializer instanceof PostSerializer) {
            $event->attributes['canLike'] = (bool) $event->model->can($event->actor, 'like');
        }
    }

    public function addRelationship(ApiRelationship $event)
    {
        if ($event->serializer instanceof PostSerializer &&
            $event->relationship === 'likes') {
            return $event->serializer->hasMany('Flarum\Api\Serializers\UserBasicSerializer', 'likes');
        }
    }

    public function includeLikes(BuildApiAction $event)
    {
        $action = $event->action;

        if ($action instanceof Discussions\ShowAction) {
            $event->addInclude('posts.likes');
        }

        if ($action instanceof Posts\IndexAction ||
            $action instanceof Posts\ShowAction ||
            $action instanceof Posts\CreateAction ||
            $action instanceof Posts\UpdateAction) {
            $event->addInclude('likes');
        }
    }
}
