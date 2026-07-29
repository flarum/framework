<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Api\Context;
use Flarum\Api\Resource;
use Flarum\Api\Schema;
use Flarum\Extend;
use Flarum\Search\Database\DatabaseSearchDriver;
use Flarum\Suspend\Access\UserPolicy;
use Flarum\Suspend\Api\UserResourceFields;
use Flarum\Suspend\Event\Suspended;
use Flarum\Suspend\Event\Unsuspended;
use Flarum\Suspend\Listener;
use Flarum\Suspend\Notification\UserSuspendedBlueprint;
use Flarum\Suspend\Notification\UserUnsuspendedBlueprint;
use Flarum\Suspend\Query\SuspendedFilter;
use Flarum\Suspend\RevokeAccessFromSuspendedUsers;
use Flarum\User\Event\Saving;
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

    (new Extend\ApiResource(Resource\UserResource::class))
        ->fields(UserResourceFields::class),

    (new Extend\ApiResource(Resource\ForumResource::class))
        ->fields(fn () => [
            Schema\Boolean::make('canSuspendUsers')
                ->get(fn (object $model, Context $context) => $context->getActor()->hasPermission('user.suspend')),
        ]),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\Notification())
        ->type(UserSuspendedBlueprint::class, ['alert', 'email'])
        ->type(UserUnsuspendedBlueprint::class, ['alert', 'email']),

    (new Extend\Event())
        ->listen(Saving::class, Listener\SavingUser::class)
        ->listen(Suspended::class, Listener\SendNotificationWhenUserIsSuspended::class)
        ->listen(Unsuspended::class, Listener\SendNotificationWhenUserIsUnsuspended::class),

    (new Extend\Policy())
        ->modelPolicy(User::class, UserPolicy::class),

    (new Extend\User())
        ->permissionGroups(RevokeAccessFromSuspendedUsers::class),

    (new Extend\SearchDriver(DatabaseSearchDriver::class))
        ->addFilter(UserSearcher::class, SuspendedFilter::class),

    (new Extend\View())
        ->namespace('flarum-suspend', __DIR__.'/views'),

    (new Extend\Conditional())
        ->whenExtensionEnabled('flarum-audit', fn () => [
            (new \Flarum\Audit\Extend\Audit())
                ->group('flarum-suspend')
                ->listen(Suspended::class, 'user.suspended', fn ($e) => array_merge(
                    ['user_id' => $e->user->id],
                    $e->user->suspended_until ? ['until' => $e->user->suspended_until->toIso8601String()] : []
                ))
                ->listen(Unsuspended::class, 'user.unsuspended', fn ($e) => ['user_id' => $e->user->id]),
        ]),
];
