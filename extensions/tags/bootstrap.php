<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Flarum\Extend;
use Flarum\Tags\Access;
use Flarum\Tags\Listener;
use Illuminate\Contracts\Events\Dispatcher;

return [
    (new Extend\Assets('forum'))
        ->asset(__DIR__.'/js/forum/dist/extension.js')
        ->asset(__DIR__.'/less/forum/extension.less')
        ->bootstrapper('flarum/tags/main'),
    (new Extend\Assets('admin'))
        ->asset(__DIR__.'/js/admin/dist/extension.js')
        ->asset(__DIR__.'/less/admin/extension.less')
        ->bootstrapper('flarum/tags/main'),
    function (Dispatcher $events) {
        $events->subscribe(Listener\AddClientAssets::class);
        $events->subscribe(Listener\AddDiscussionTagsRelationship::class);
        $events->subscribe(Listener\AddForumTagsRelationship::class);
        $events->subscribe(Listener\AddTagsApi::class);
        $events->subscribe(Listener\CreatePostWhenTagsAreChanged::class);
        $events->subscribe(Listener\FilterDiscussionListByTags::class);
        $events->subscribe(Listener\FilterPostsQueryByTag::class);
        $events->subscribe(Listener\SaveTagsToDatabase::class);
        $events->subscribe(Listener\UpdateTagMetadata::class);

        $events->subscribe(Access\GlobalPolicy::class);
        $events->subscribe(Access\DiscussionPolicy::class);
        $events->subscribe(Access\TagPolicy::class);
        $events->subscribe(Access\FlagPolicy::class);
    },
];
