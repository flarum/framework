<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Extend;
use Flarum\Sticky\Listener;
use Illuminate\Contracts\Events\Dispatcher;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),

    function (Dispatcher $events) {
        $events->subscribe(Listener\AddApiAttributes::class);
        $events->subscribe(Listener\CreatePostWhenDiscussionIsStickied::class);
        $events->subscribe(Listener\PinStickiedDiscussionsToTop::class);
        $events->subscribe(Listener\SaveStickyToDatabase::class);
    },
];
