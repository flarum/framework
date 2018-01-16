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
use Flarum\Sticky\Listener;
use Illuminate\Contracts\Events\Dispatcher;

return [
    (new Extend\Assets('forum'))
        ->defaultAssets(__DIR__)
        ->bootstrapper('flarum/sticky/main'),
    (new Extend\Assets('admin'))
        ->asset(__DIR__.'/js/admin/dist/extension.js')
        ->bootstrapper('flarum/sticky/main'),
    function (Dispatcher $events) {
        $events->subscribe(Listener\AddApiAttributes::class);
        $events->subscribe(Listener\CreatePostWhenDiscussionIsStickied::class);
        $events->subscribe(Listener\PinStickiedDiscussionsToTop::class);
        $events->subscribe(Listener\SaveStickyToDatabase::class);
    },
];
