<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Flarum\Api\Event\Serializing;
use Flarum\Discussion\Event\Saving;
use Flarum\Extend;
use Flarum\Tags\Access;
use Flarum\Tags\Api\Controller;
use Flarum\Tags\Content;
use Flarum\Tags\Listener;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\View\Factory;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less')
        ->route('/t/{slug}', 'tag', Content\Tag::class)
        ->route('/tags', 'tags', Content\Tags::class),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/less/admin.less'),

    (new Extend\Routes('api'))
        ->get('/tags', 'tags.index', Controller\ListTagsController::class)
        ->post('/tags', 'tags.create', Controller\CreateTagController::class)
        ->post('/tags/order', 'tags.order', Controller\OrderTagsController::class)
        ->patch('/tags/{id}', 'tags.update', Controller\UpdateTagController::class)
        ->delete('/tags/{id}', 'tags.delete', Controller\DeleteTagController::class),

    function (Dispatcher $events, Factory $view) {
        $events->subscribe(Listener\AddDiscussionTagsRelationship::class);

        $events->subscribe(Listener\AddForumTagsRelationship::class);
        $events->listen(Serializing::class, Listener\PrepareForumTagsApiAttributes::class);

        $events->subscribe(Listener\CreatePostWhenTagsAreChanged::class);
        $events->subscribe(Listener\FilterDiscussionListByTags::class);
        $events->subscribe(Listener\FilterPostsQueryByTag::class);
        $events->listen(Saving::class, Listener\SaveTagsToDatabase::class);
        $events->subscribe(Listener\UpdateTagMetadata::class);

        $events->subscribe(Access\GlobalPolicy::class);
        $events->subscribe(Access\DiscussionPolicy::class);
        $events->subscribe(Access\TagPolicy::class);
        $events->subscribe(Access\FlagPolicy::class);

        $view->addNamespace('tags', __DIR__.'/views');
    },
];
