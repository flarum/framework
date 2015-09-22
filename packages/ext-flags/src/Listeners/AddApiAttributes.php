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

use Flarum\Events\ApiRelationship;
use Flarum\Events\WillSerializeData;
use Flarum\Events\BuildApiAction;
use Flarum\Events\ApiAttributes;
use Flarum\Events\RegisterApiRoutes;
use Flarum\Api\Serializers\PostSerializer;
use Flarum\Api\Serializers\ForumSerializer;
use Flarum\Api\Actions\Posts;
use Flarum\Api\Actions\Discussions;
use Flarum\Flags\Flag;
use Flarum\Flags\Api\CreateAction as FlagsCreateAction;
use Illuminate\Database\Eloquent\Collection;

class AddApiAttributes
{
    public function subscribe($events)
    {
        $events->listen(ApiRelationship::class, [$this, 'addFlagsRelationship']);
        $events->listen(WillSerializeData::class, [$this, 'loadFlagsRelationship']);
        $events->listen(BuildApiAction::class, [$this, 'includeFlagsRelationship']);
        $events->listen(ApiAttributes::class, [$this, 'addAttributes']);
        $events->listen(RegisterApiRoutes::class, [$this, 'addRoutes']);
    }

    public function loadFlagsRelationship(WillSerializeData $event)
    {
        // For any API action that allows the 'flags' relationship to be
        // included, we need to preload this relationship onto the data (Post
        // models) so that we can selectively expose only the flags that the
        // user has permission to view.
        if ($event->action instanceof Discussions\ShowAction) {
            $discussion = $event->data;
            $posts = $discussion->posts->all();
        }

        if ($event->action instanceof Posts\IndexAction) {
            $posts = $event->data->all();
        }

        if ($event->action instanceof Posts\ShowAction) {
            $posts = [$event->data];
        }

        if ($event->action instanceof FlagsCreateAction) {
            $flag = $event->data;
            $posts = [$flag->post];
        }

        if (isset($posts)) {
            $actor = $event->request->actor;
            $postsWithPermission = [];

            foreach ($posts as $post) {
                $post->setRelation('flags', null);

                if ($post->discussion->can($actor, 'viewFlags')) {
                    $postsWithPermission[] = $post;
                }
            }

            if (count($postsWithPermission)) {
                (new Collection($postsWithPermission))
                    ->load('flags', 'flags.user');
            }
        }
    }

    public function addFlagsRelationship(ApiRelationship $event)
    {
        if ($event->serializer instanceof PostSerializer &&
            $event->relationship === 'flags') {
            return $event->serializer->hasMany('Flarum\Flags\Api\FlagSerializer', 'flags');
        }
    }

    public function includeFlagsRelationship(BuildApiAction $event)
    {
        if ($event->action instanceof Discussions\ShowAction) {
            $event->addInclude('posts.flags');
            $event->addInclude('posts.flags.user');
        }

        if ($event->action instanceof Posts\IndexAction ||
            $event->action instanceof Posts\ShowAction) {
            $event->addInclude('flags');
            $event->addInclude('flags.user');
        }
    }

    public function addAttributes(ApiAttributes $event)
    {
        if ($event->serializer instanceof ForumSerializer) {
            $event->attributes['canViewFlags'] = $event->actor->hasPermissionLike('discussion.viewFlags');

            if ($event->attributes['canViewFlags']) {
                $query = Flag::whereVisibleTo($event->actor);

                if ($time = $event->actor->flags_read_time) {
                    $query->where('flags.time', '>', $time);
                }

                $event->attributes['unreadFlagsCount'] = $query->distinct('flags.post_id')->count();
            }
        }

        if ($event->serializer instanceof PostSerializer) {
            $event->attributes['canFlag'] = $event->model->can($event->actor, 'flag');
        }
    }

    public function addRoutes(RegisterApiRoutes $event)
    {
        $event->get('/flags', 'flags.index', 'Flarum\Flags\Api\IndexAction');
        $event->post('/flags', 'flags.create', 'Flarum\Flags\Api\CreateAction');
        $event->delete('/posts/{id}/flags', 'flags.delete', 'Flarum\Flags\Api\DeleteAction');
    }
}
