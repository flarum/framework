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

use Flarum\Events\RegisterPostTypes;
use Flarum\Tags\Posts\DiscussionTaggedPost;
use Flarum\Tags\Events\DiscussionWasTagged;
use Illuminate\Contracts\Events\Dispatcher;

class LogDiscussionTagged
{
    public function subscribe(Dispatcher $events)
    {
        $events->listen(RegisterPostTypes::class, [$this, 'registerPostType']);
        $events->listen(DiscussionWasTagged::class, [$this, 'whenDiscussionWasTagged']);
    }

    public function registerPostType(RegisterPostTypes $event)
    {
        $event->register(DiscussionTaggedPost::class);
    }

    public function whenDiscussionWasTagged(DiscussionWasTagged $event)
    {
        $post = DiscussionTaggedPost::reply(
            $event->discussion->id,
            $event->user->id,
            array_pluck($event->oldTags, 'id'),
            $event->discussion->tags()->lists('id')->all()
        );

        $event->discussion->mergePost($post);
    }
}
