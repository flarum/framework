<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Flarum\Akismet\Listener;
use Illuminate\Contracts\Events\Dispatcher;

return [
    (new \Flarum\Extend\Assets('forum'))
        ->asset(__DIR__.'/js/forum/dist/extension.js')
        ->bootstrapper('flarum/akismet/main'),
    (new \Flarum\Extend\Assets('admin'))
        ->asset(__DIR__.'/js/admin/dist/extension.js')
        ->bootstrapper('flarum/akismet/main'),
    function (Dispatcher $events) {
        $events->subscribe(Listener\FilterNewPosts::class);
    }
];
