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
use Flarum\Lock\Access;
use Flarum\Lock\Listener;
use Illuminate\Contracts\Events\Dispatcher;

return [
    (new Extend\Assets('forum'))
        ->defaultAssets(__DIR__)
        ->bootstrapper('flarum/lock/main'),
    (new Extend\Assets('admin'))
        ->asset(__DIR__.'/js/admin/dist/extension.js')
        ->bootstrapper('flarum/lock/main'),
    function (Dispatcher $events) {
        $events->subscribe(Listener\AddDiscussionLockedAttributes::class);
        $events->subscribe(Listener\AddLockedGambit::class);
        $events->subscribe(Listener\CreatePostWhenDiscussionIsLocked::class);
        $events->subscribe(Listener\SaveLockedToDatabase::class);

        $events->subscribe(Access\DiscussionPolicy::class);
        $events->subscribe(Access\PostPolicy::class);
    },
];
