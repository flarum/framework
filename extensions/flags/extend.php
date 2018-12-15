<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Flarum\Api\Event\Serializing;
use Flarum\Event\ConfigureModelDates;
use Flarum\Extend;
use Flarum\Flags\Api\Controller\CreateFlagController;
use Flarum\Flags\Api\Controller\DeleteFlagsController;
use Flarum\Flags\Api\Controller\ListFlagsController;
use Flarum\Flags\Listener;
use Illuminate\Contracts\Events\Dispatcher;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),

    (new Extend\Routes('api'))
        ->get('/flags', 'flags.index', ListFlagsController::class)
        ->post('/flags', 'flags.create', CreateFlagController::class)
        ->delete('/posts/{id}/flags', 'flags.delete', DeleteFlagsController::class),

    function (Dispatcher $events) {
        $events->listen(ConfigureModelDates::class, Listener\AddFlagsApiDates::class);
        $events->listen(Serializing::class, Listener\AddFlagsApiAttributes::class);

        $events->subscribe(Listener\AddPostFlagsRelationship::class);
    },
];
