<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Api\Serializer\BasicDiscussionSerializer;
use Flarum\Api\Serializer\PostSerializer;
use Flarum\Approval\Access;
use Flarum\Approval\Listener;
use Flarum\Discussion\Discussion;
use Flarum\Extend;
use Flarum\Post\Post;
use Illuminate\Contracts\Events\Dispatcher;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),

    // Discussions should be approved by default
    (new Extend\Model(Discussion::class))
        ->default('is_approved', true),

    // Posts should be approved by default
    (new Extend\Model(Post::class))
        ->default('is_approved', true),

    (new Extend\ApiSerializer(BasicDiscussionSerializer::class))
        ->attribute('isApproved', function ($serializer, Discussion $discussion) {
            return (bool) $discussion->is_approved;
        }),

    (new Extend\ApiSerializer(PostSerializer::class))
        ->attribute('isApproved', function ($serializer, Post $post) {
            return (bool) $post->is_approved;
        })->attribute('canApprove', function (PostSerializer $serializer, Post $post) {
            return (bool) $serializer->getActor()->can('approvePosts', $post->discussion);
        }),

    new Extend\Locales(__DIR__.'/locale'),

    function (Dispatcher $events) {
        $events->subscribe(Listener\ApproveContent::class);
        $events->subscribe(Listener\UnapproveNewContent::class);

        $events->subscribe(Access\TagPolicy::class);
        $events->subscribe(Access\DiscussionPolicy::class);
        $events->subscribe(Access\PostPolicy::class);
    },
];
