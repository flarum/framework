<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Api\Serializer\BasicUserSerializer;
use Flarum\Api\Serializer\UserSerializer;
use Flarum\Extend;
use Flarum\Suspend\Access\UserPolicy;
use Flarum\Suspend\AddUserSuspendAttributes;
use Flarum\Suspend\Event\Suspended;
use Flarum\Suspend\Event\Unsuspended;
use Flarum\Suspend\Listener;
use Flarum\Suspend\Notification\UserSuspendedBlueprint;
use Flarum\Suspend\Notification\UserUnsuspendedBlueprint;
use Flarum\Suspend\Query\SuspendedFilterGambit;
use Flarum\Suspend\RevokeAccessFromSuspendedUsers;
use Flarum\User\Event\Saving;
use Flarum\User\Filter\UserFilterer;
use Flarum\User\Search\UserSearcher;
use Flarum\User\User;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/less/admin.less'),

    (new Extend\Model(User::class))
        ->cast('suspended_until', 'datetime')
        ->cast('suspend_reason', 'string')
        ->cast('suspend_message', 'string'),

    (new Extend\ApiSerializer(UserSerializer::class))
        ->attributes(AddUserSuspendAttributes::class),

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

    (new Extend\Filter(UserFilterer::class))
        ->addFilter(SuspendedFilterGambit::class),

    (new Extend\SimpleFlarumSearch(UserSearcher::class))
        ->addGambit(SuspendedFilterGambit::class),

    (new Extend\View())
        ->namespace('flarum-suspend', __DIR__.'/views'),
];
