<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Api\Event\Serializing;
use Flarum\Api\Serializer\BasicDiscussionSerializer;
use Flarum\Discussion\Event\Saving;
use Flarum\Discussion\Event\Searching;
use Flarum\Event\ConfigureDiscussionGambits;
use Flarum\Event\ConfigureUserPreferences;
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

    function (Dispatcher $events) {
        $events->listen(Serializing::class, Listener\AddDiscussionSubscriptionAttribute::class);
        $events->listen(Saving::class, Listener\SaveSubscriptionToDatabase::class);

        $events->listen(ConfigureDiscussionGambits::class, function (ConfigureDiscussionGambits $event) {
            $event->gambits->add(SubscriptionGambit::class);
        });
        $events->listen(Searching::class, Listener\FilterDiscussionListBySubscription::class);

        $events->listen(Posted::class, Listener\SendNotificationWhenReplyIsPosted::class);
        $events->listen(Hidden::class, Listener\DeleteNotificationWhenPostIsHiddenOrDeleted::class);
        $events->listen(Restored::class, Listener\RestoreNotificationWhenPostIsRestored::class);
        $events->listen(Deleted::class, Listener\DeleteNotificationWhenPostIsHiddenOrDeleted::class);

        $events->listen(ConfigureUserPreferences::class, function (ConfigureUserPreferences $event) {
            $event->add('followAfterReply', 'boolval', false);
        });
        $events->listen(Posted::class, Listener\FollowAfterReply::class);
    }
];
