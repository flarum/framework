<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Flarum\Extend;
use Flarum\Suspend\Access;
use Flarum\Suspend\Listener;
use Illuminate\Contracts\Events\Dispatcher;

return [
    (new Extend\Assets('forum'))
        ->asset(__DIR__.'/js/forum/dist/extension.js')
        ->asset(__DIR__.'/less/forum/extension.less')
        ->bootstrapper('flarum/suspend/main'),
    (new Extend\Assets('admin'))
        ->asset(__DIR__.'/js/admin/dist/extension.js')
        ->asset(__DIR__.'/less/admin/extension.less')
        ->bootstrapper('flarum/suspend/main'),
    function (Dispatcher $events) {
        $events->subscribe(Listener\AddUserSuspendAttributes::class);
        $events->subscribe(Listener\RevokeAccessFromSuspendedUsers::class);
        $events->subscribe(Listener\SaveSuspensionToDatabase::class);
        $events->subscribe(Listener\SendNotificationWhenUserIsSuspended::class);

        $events->subscribe(Access\UserPolicy::class);
    }
];
