<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Api\Serializer\BasicUserSerializer;
use Flarum\Api\Serializer\UserSerializer;
use Flarum\Event\ConfigureUserGambits;
use Flarum\Extend;
use Flarum\Suspend\Access\UserPolicy;
use Flarum\Suspend\AddUserSuspendAttributes;
use Flarum\Suspend\Event\Suspended;
use Flarum\Suspend\Event\Unsuspended;
use Flarum\Suspend\Listener;
use Flarum\Suspend\Notification\UserSuspendedBlueprint;
use Flarum\Suspend\Notification\UserUnsuspendedBlueprint;
use Flarum\Suspend\RevokeAccessFromSuspendedUsers;
use Flarum\Suspend\Search\Gambit\SuspendedGambit;
use Flarum\User\Event\Saving;
use Flarum\User\User;
use Illuminate\Contracts\Events\Dispatcher;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/less/admin.less'),

    (new Extend\Model(User::class))
        ->dateAttribute('suspended_until'),

    (new Extend\ApiSerializer(UserSerializer::class))
        ->mutate(AddUserSuspendAttributes::class),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\Notification())
        ->type(UserSuspendedBlueprint::class, BasicUserSerializer::class, ['alert', 'email'])
        ->type(UserUnsuspendedBlueprint::class, BasicUserSerializer::class, ['alert', 'email']),

    (new Extend\Event())
        ->listen(Saving::class, Listener\SaveSuspensionToDatabase::class)
        ->listen(Suspended::class, Listener\SendNotificationWhenUserIsSuspended::class)
        ->listen(Unsuspended::class, Listener\SendNotificationWhenUserIsUnsuspended::class),

    (new Extend\Policy())
        ->modelPolicy(User::class, UserPolicy::class),

    (new Extend\User())
        ->permissionGroups(RevokeAccessFromSuspendedUsers::class),

    function (Dispatcher $events) {
        $events->listen(ConfigureUserGambits::class, function (ConfigureUserGambits $event) {
            $event->gambits->add(SuspendedGambit::class);
        });
    }
];
