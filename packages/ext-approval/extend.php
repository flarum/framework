<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

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

    function (Dispatcher $events) {
        $events->subscribe(Listener\AddPostApprovalAttributes::class);
        $events->subscribe(Listener\ApproveContent::class);
        $events->subscribe(Listener\UnapproveNewContent::class);

        $events->subscribe(Access\TagPolicy::class);
        $events->subscribe(Access\DiscussionPolicy::class);
        $events->subscribe(Access\PostPolicy::class);
    },
];
