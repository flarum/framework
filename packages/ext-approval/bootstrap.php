<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Flarum\Approval\Access;
use Flarum\Approval\Listener;
use Flarum\Extend;
use Illuminate\Contracts\Events\Dispatcher;

return [
    (new Extend\Assets('forum'))
        ->defaultAssets(__DIR__)
        ->bootstrapper('flarum/approval/main'),
    (new Extend\Assets('admin'))
        ->asset(__DIR__.'/js/admin/dist/extension.js')
        ->bootstrapper('flarum/approval/main'),
    function (Dispatcher $events) {
        $events->subscribe(Listener\AddPostApprovalAttributes::class);
        $events->subscribe(Listener\ApproveContent::class);
        $events->subscribe(Listener\HideUnapprovedContent::class);
        $events->subscribe(Listener\UnapproveNewContent::class);

        $events->subscribe(Access\TagPolicy::class);
        $events->subscribe(Access\DiscussionPolicy::class);
        $events->subscribe(Access\PostPolicy::class);
    },
];
