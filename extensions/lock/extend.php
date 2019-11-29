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
use Flarum\Event\ConfigureDiscussionGambits;
use Flarum\Event\ConfigureNotificationTypes;
use Flarum\Event\ConfigurePostTypes;
use Flarum\Extend;
use Flarum\Lock\Access;
use Flarum\Lock\Event\DiscussionWasLocked;
use Flarum\Lock\Event\DiscussionWasUnlocked;
use Flarum\Lock\Gambit\LockedGambit;
use Flarum\Lock\Listener;
use Flarum\Lock\Notification\DiscussionLockedBlueprint;
use Flarum\Lock\Post\DiscussionLockedPost;
use Illuminate\Contracts\Events\Dispatcher;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),

    function (Dispatcher $events) {
        $events->listen(ConfigureDiscussionGambits::class, function (ConfigureDiscussionGambits $event) {
            $event->gambits->add(LockedGambit::class);
        });
        $events->listen(Serializing::class, Listener\AddDiscussionLockedAttributes::class);
        $events->listen(Saving::class, Listener\SaveLockedToDatabase::class);

        $events->listen(ConfigurePostTypes::class, function (ConfigurePostTypes $event) {
            $event->add(DiscussionLockedPost::class);
        });
        $events->listen(ConfigureNotificationTypes::class, function (ConfigureNotificationTypes $event) {
            $event->add(DiscussionLockedBlueprint::class, BasicDiscussionSerializer::class, ['alert']);
        });
        $events->listen(DiscussionWasLocked::class, Listener\CreatePostWhenDiscussionIsLocked::class);
        $events->listen(DiscussionWasUnlocked::class, Listener\CreatePostWhenDiscussionIsUnlocked::class);

        $events->subscribe(Access\DiscussionPolicy::class);
    },
];
