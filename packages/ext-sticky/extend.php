<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Api\Controller\ListDiscussionsController;
use Flarum\Api\Serializer\DiscussionSerializer;
use Flarum\Discussion\Event\Saving;
use Flarum\Extend;
use Flarum\Sticky\Event\DiscussionWasStickied;
use Flarum\Sticky\Event\DiscussionWasUnstickied;
use Flarum\Sticky\Listener;
use Flarum\Sticky\Listener\SaveStickyToDatabase;
use Flarum\Sticky\Post\DiscussionStickiedPost;
use Illuminate\Contracts\Events\Dispatcher;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less'),

    (new Extend\Post())
        ->type(DiscussionStickiedPost::class),

    (new Extend\ApiSerializer(DiscussionSerializer::class))
        ->attribute('isSticky', function (DiscussionSerializer $serializer, $discussion) {
            return (bool) $discussion->is_sticky;
        })
        ->attribute('canSticky', function (DiscussionSerializer $serializer, $discussion) {
            return (bool) $serializer->getActor()->can('sticky', $discussion);
        }),

    (new Extend\ApiController(ListDiscussionsController::class))
        ->addInclude('firstPost'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\Event())
        ->listen(Saving::class, SaveStickyToDatabase::class)
        ->listen(DiscussionWasStickied::class, [Listener\CreatePostWhenDiscussionIsStickied::class, 'whenDiscussionWasStickied'])
        ->listen(DiscussionWasUnstickied::class, [Listener\CreatePostWhenDiscussionIsStickied::class, 'whenDiscussionWasUnstickied']),

    function (Dispatcher $events) {
        // Replace with Filter extender before stable
        $events->subscribe(Listener\PinStickiedDiscussionsToTop::class);
    },
];
