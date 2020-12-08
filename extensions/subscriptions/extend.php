<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Api\Serializer\BasicDiscussionSerializer;
use Flarum\Api\Serializer\DiscussionSerializer;
use Flarum\Discussion\Discussion;
use Flarum\Discussion\Event\Saving;
use Flarum\Discussion\Event\Searching;
use Flarum\Event\ConfigureDiscussionGambits;
use Flarum\Extend;
use Flarum\Post\Event\Deleted;
use Flarum\Post\Event\Hidden;
use Flarum\Post\Event\Posted;
use Flarum\Post\Event\Restored;
use Flarum\Subscriptions\Gambit\SubscriptionGambit;
use Flarum\Subscriptions\Listener;
use Flarum\Subscriptions\Notification\NewPostBlueprint;
use Illuminate\Contracts\Events\Dispatcher;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less')
        ->route('/following', 'following'),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\View)
        ->namespace('flarum-subscriptions', __DIR__.'/views'),

    (new Extend\Notification())
        ->type(NewPostBlueprint::class, BasicDiscussionSerializer::class, ['alert', 'email']),

    (new Extend\ApiSerializer(DiscussionSerializer::class))
        ->attribute('subscription', function (DiscussionSerializer $serializer, Discussion $discussion) {
            if ($state = $discussion->state) {
                return $state->subscription ?: false;
            }
        }),

    (new Extend\User())
        ->registerPreference('followAfterReply', 'boolval', false),

    (new Extend\Event())
        ->listen(Saving::class, Listener\SaveSubscriptionToDatabase::class)
        ->listen(Posted::class, Listener\SendNotificationWhenReplyIsPosted::class)
        ->listen(Hidden::class, Listener\DeleteNotificationWhenPostIsHiddenOrDeleted::class)
        ->listen(Restored::class, Listener\RestoreNotificationWhenPostIsRestored::class)
        ->listen(Deleted::class, Listener\DeleteNotificationWhenPostIsHiddenOrDeleted::class)
        ->listen(Posted::class, Listener\FollowAfterReply::class),

    function (Dispatcher $events) {
        $events->listen(ConfigureDiscussionGambits::class, function (ConfigureDiscussionGambits $event) {
            $event->gambits->add(SubscriptionGambit::class);
        });
        $events->listen(Searching::class, Listener\FilterDiscussionListBySubscription::class);
    }
];
