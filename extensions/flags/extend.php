<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Api\Endpoint;
use Flarum\Api\Resource;
use Flarum\Extend;
use Flarum\Flags\Access\ScopeFlagVisibility;
use Flarum\Flags\Api\Controller\DeleteFlagsController;
use Flarum\Flags\Api\ForumResourceFields;
use Flarum\Flags\Api\PostResourceFields;
use Flarum\Flags\Api\Resource\FlagResource;
use Flarum\Flags\Api\UserResourceFields;
use Flarum\Flags\AuditIntegration;
use Flarum\Flags\Event\Created as FlagCreated;
use Flarum\Flags\Event\Deleting as FlagDeleting;
use Flarum\Flags\Flag;
use Flarum\Flags\Listener;
use Flarum\Forum\Content\AssertRegistered;
use Flarum\Post\Event\Deleted;
use Flarum\Post\Post;
use Flarum\Realtime\Extend\Realtime as RealtimeExtend;
use Flarum\User\User;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->jsDirectory(__DIR__.'/js/dist/forum')
        ->css(__DIR__.'/less/forum.less')
        ->route('/flags', 'flags', AssertRegistered::class),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),

    (new Extend\Routes('api'))
        ->delete('/posts/{id}/flags', 'flags.delete', DeleteFlagsController::class),

    (new Extend\Model(User::class))
        ->cast('read_flags_at', 'datetime'),

    (new Extend\Model(Post::class))
        ->hasMany('flags', Flag::class, 'post_id'),

    new Extend\ApiResource(FlagResource::class),

    (new Extend\ApiResource(Resource\PostResource::class))
        ->fields(PostResourceFields::class),

    (new Extend\ApiResource(Resource\UserResource::class))
        ->fields(UserResourceFields::class),

    (new Extend\ApiResource(Resource\ForumResource::class))
        ->fields(ForumResourceFields::class),

    (new Extend\ApiResource(Resource\PostResource::class))
        ->endpoint([Endpoint\Index::class, Endpoint\Show::class], function (Endpoint\Index|Endpoint\Show $endpoint) {
            return $endpoint->addDefaultInclude(['flags', 'flags.user']);
        }),

    (new Extend\Settings())
        ->serializeToForum('guidelinesUrl', 'flarum-flags.guidelines_url'),

    (new Extend\Event())
        ->listen(Deleted::class, Listener\DeleteFlags::class),

    (new Extend\ModelVisibility(Flag::class))
        ->scope(ScopeFlagVisibility::class),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\Conditional())
        ->whenExtensionEnabled('flarum-realtime', fn () => [
            (new RealtimeExtend())
                ->broadcastFlagEvent(
                    [FlagCreated::class, FlagDeleting::class],
                    fn ($event) => $event->flag->post->discussion,
                    'flagged'
                ),
        ])
        ->whenExtensionEnabled('flarum-audit', fn () => [
            (new \Flarum\Audit\Extend\Audit())
                ->group('flarum-flags')
                ->using(new AuditIntegration()),
        ]),
];
