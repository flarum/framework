<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Flarum\Api\Serializer\BasicUserSerializer;
use Flarum\Event\ConfigureNotificationTypes;
use Flarum\Extend;
use Flarum\Suspend\Access;
use Flarum\Suspend\Event\Suspended;
use Flarum\Suspend\Event\Unsuspended;
use Flarum\Suspend\Listener;
use Flarum\Suspend\Notification\UserSuspendedBlueprint;
use Flarum\Suspend\Notification\UserUnsuspendedBlueprint;
use Flarum\User\Event\Saving;
use Illuminate\Contracts\Events\Dispatcher;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/less/admin.less'),

    function (Dispatcher $events) {
        $events->subscribe(Listener\AddUserSuspendAttributes::class);
        $events->subscribe(Listener\RevokeAccessFromSuspendedUsers::class);

        $events->listen(Saving::class, Listener\SaveSuspensionToDatabase::class);

        $events->listen(ConfigureNotificationTypes::class, function (ConfigureNotificationTypes $event) {
            $event->add(UserSuspendedBlueprint::class, BasicUserSerializer::class, ['alert', 'email']);
            $event->add(UserUnsuspendedBlueprint::class, BasicUserSerializer::class, ['alert', 'email']);
        });
        $events->listen(Suspended::class, Listener\SendNotificationWhenUserIsSuspended::class);
        $events->listen(Unsuspended::class, Listener\SendNotificationWhenUserIsUnsuspended::class);

        $events->subscribe(Access\UserPolicy::class);
    }
];
