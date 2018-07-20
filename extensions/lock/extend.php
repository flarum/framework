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
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),

    function (Dispatcher $events) {
        $events->subscribe(Listener\AddDiscussionLockedAttributes::class);
        $events->subscribe(Listener\AddLockedGambit::class);
        $events->subscribe(Listener\CreatePostWhenDiscussionIsLocked::class);
        $events->subscribe(Listener\SaveLockedToDatabase::class);

        $events->subscribe(Access\DiscussionPolicy::class);
    },
];
