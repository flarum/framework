<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Api\Context;
use Flarum\Api\Resource\ForumResource;
use Flarum\Api\Schema;
use Flarum\Discussion\Discussion;
use Flarum\Extend;
use Flarum\Post\Post;
use Flarum\Post\RegisteredTypesScope;
use Flarum\Statistics\Extend\Statistics;
use Flarum\User\User;

return [
    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/less/admin.less'),

    new Extend\Locales(__DIR__.'/locale'),
    (new Extend\Routes('api'))
        ->get('/statistics', 'flarum-statistics.get-statistics', Flarum\Statistics\Api\Controller\ShowStatisticsData::class),

    (new Statistics())
        ->entity('users', fn () => User::query(), 'joined_at')
        ->entity('discussions', fn () => Discussion::query(), 'created_at')
        ->entity('posts', fn () => Post::where('type', 'comment')->withoutGlobalScope(RegisteredTypesScope::class), 'created_at'),

    // Which statistics there are, so the widgets can lay them out before
    // their numbers arrive. Only admins can see statistics at all.
    (new Extend\ApiResource(ForumResource::class))
        ->fields(fn () => [
            Schema\Arr::make('statisticsEntities')
                ->visible(fn (object $model, Context $context) => $context->getActor()->isAdmin())
                ->get(fn () => array_keys(resolve('flarum-statistics.entities'))),
        ]),
];
