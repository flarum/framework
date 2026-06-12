<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Api\Endpoint;
use Flarum\Api\Resource;
use Flarum\Discussion\Discussion;
use Flarum\Discussion\Search\DiscussionSearcher;
use Flarum\Extend;
use Flarum\Realtime\Extend\Realtime as RealtimeExtend;
use Flarum\Search\Database\DatabaseSearchDriver;
use Flarum\Sticky\Api\DiscussionResourceFields;
use Flarum\Sticky\Event\DiscussionWasStickied;
use Flarum\Sticky\Event\DiscussionWasUnstickied;
use Flarum\Sticky\Listener;
use Flarum\Sticky\PinStickiedDiscussionsToTop;
use Flarum\Sticky\Post\DiscussionStickiedPost;
use Flarum\Sticky\Query\StickyFilter;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\Settings())
        ->serializeToForum('excerptDisplayEnabled', 'flarum-sticky.enable_display_excerpt', 'boolval')
        ->default('flarum-sticky.enable_display_excerpt', true),

    (new Extend\Model(Discussion::class))
        ->cast('is_sticky', 'bool'),

    (new Extend\Post())
        ->type(DiscussionStickiedPost::class),

    (new Extend\ApiResource(Resource\DiscussionResource::class))
        ->fields(DiscussionResourceFields::class)
        ->endpoint(Endpoint\Index::class, function (Endpoint\Index $endpoint): Endpoint\Index {
            return $endpoint->addDefaultInclude(['firstPost']);
        }),

    (new Extend\Event())
        ->listen(DiscussionWasStickied::class, [Listener\CreatePostWhenDiscussionIsStickied::class, 'whenDiscussionWasStickied'])
        ->listen(DiscussionWasUnstickied::class, [Listener\CreatePostWhenDiscussionIsStickied::class, 'whenDiscussionWasUnstickied']),

    (new Extend\SearchDriver(DatabaseSearchDriver::class))
        ->addFilter(DiscussionSearcher::class, StickyFilter::class)
        ->addMutator(DiscussionSearcher::class, PinStickiedDiscussionsToTop::class),

    (new Extend\Settings())
        ->default('flarum-sticky.only_sticky_unread_discussions', true)
        ->serializeToForum('onlyStickyUnreadDiscussions', 'flarum-sticky.only_sticky_unread_discussions', 'boolval'),

    (new Extend\Conditional())
        ->whenExtensionEnabled('flarum-realtime', fn () => [
            (new RealtimeExtend())
                ->broadcastModelEvent(
                    [DiscussionWasStickied::class, DiscussionWasUnstickied::class],
                    fn ($event) => $event->discussion,
                    fn ($event) => $event->user,
                    'stickiedEvent'
                ),
        ])
        ->whenExtensionEnabled('flarum-audit', fn () => [
            (new \Flarum\Audit\Extend\Audit())
                ->group('flarum-sticky')
                ->listen(DiscussionWasStickied::class, 'discussion.stickied', fn ($e) => ['discussion_id' => $e->discussion->id])
                ->listen(DiscussionWasUnstickied::class, 'discussion.unstickied', fn ($e) => ['discussion_id' => $e->discussion->id]),
        ]),
];
