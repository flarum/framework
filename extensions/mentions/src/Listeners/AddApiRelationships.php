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

use Flarum\Events\ApiRelationship;
use Flarum\Events\BuildApiAction;
use Illuminate\Contracts\Events\Dispatcher;
use Flarum\Api\Serializers\PostBasicSerializer;
use Flarum\Api\Actions\Discussions;
use Flarum\Api\Actions\Posts;

class AddApiRelationships
{
    public function subscribe(Dispatcher $events)
    {
        $events->listen(ApiRelationship::class, [$this, 'addRelationships']);
        $events->listen(BuildApiAction::class, [$this, 'includeRelationships']);
    }

    public function addRelationships(ApiRelationship $event)
    {
        if ($event->serializer instanceof PostBasicSerializer) {
            if ($event->relationship === 'mentionedBy') {
                return $event->serializer->hasMany('Flarum\Api\Serializers\PostBasicSerializer', 'mentionedBy');
            }

            if ($event->relationship === 'mentionsPosts') {
                return $event->serializer->hasMany('Flarum\Api\Serializers\PostBasicSerializer', 'mentionsPosts');
            }

            if ($event->relationship === 'mentionsUsers') {
                return $event->serializer->hasMany('Flarum\Api\Serializers\PostBasicSerializer', 'mentionsUsers');
            }
        }
    }

    public function includeRelationships(BuildApiAction $event)
    {
        if ($event->action instanceof Discussions\ShowAction) {
            $event->addInclude('posts.mentionedBy');
            $event->addInclude('posts.mentionedBy.user');
            $event->addInclude('posts.mentionedBy.discussion');
        }

        if ($event->action instanceof Posts\ShowAction ||
            $event->action instanceof Posts\IndexAction) {
            $event->addInclude('mentionedBy');
            $event->addInclude('mentionedBy.user');
            $event->addInclude('mentionedBy.discussion');
        }

        if ($event->action instanceof Posts\CreateAction) {
            $event->addInclude('mentionsPosts');
            $event->addInclude('mentionsPosts.mentionedBy');
        }
    }
}
