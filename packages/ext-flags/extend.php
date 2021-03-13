<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Api\Controller\AbstractSerializeController;
use Flarum\Api\Controller\ListPostsController;
use Flarum\Api\Controller\ShowDiscussionController;
use Flarum\Api\Controller\ShowPostController;
use Flarum\Api\Serializer\CurrentUserSerializer;
use Flarum\Api\Serializer\ForumSerializer;
use Flarum\Api\Serializer\PostSerializer;
use Flarum\Extend;
use Flarum\Flags\AddCanFlagAttribute;
use Flarum\Flags\AddFlagsApiAttributes;
use Flarum\Flags\AddNewFlagCountAttribute;
use Flarum\Flags\Api\Controller\CreateFlagController;
use Flarum\Flags\Api\Controller\DeleteFlagsController;
use Flarum\Flags\Api\Controller\ListFlagsController;
use Flarum\Flags\Api\Serializer\FlagSerializer;
use Flarum\Flags\Flag;
use Flarum\Flags\Listener;
use Flarum\Flags\PrepareFlagsApiData;
use Flarum\Forum\Content\AssertRegistered;
use Flarum\Post\Event\Deleted;
use Flarum\Post\Post;
use Flarum\User\User;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less')
        ->route('/flags', 'flags', AssertRegistered::class),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),

    (new Extend\Routes('api'))
        ->get('/flags', 'flags.index', ListFlagsController::class)
        ->post('/flags', 'flags.create', CreateFlagController::class)
        ->delete('/posts/{id}/flags', 'flags.delete', DeleteFlagsController::class),

    (new Extend\Model(User::class))
        ->dateAttribute('read_flags_at'),

    (new Extend\Model(Post::class))
        ->hasMany('flags', Flag::class, 'post_id'),

    (new Extend\ApiSerializer(PostSerializer::class))
        ->hasMany('flags', FlagSerializer::class)
        ->attribute('canFlag', AddCanFlagAttribute::class),

    (new Extend\ApiSerializer(CurrentUserSerializer::class))
        ->attribute('newFlagCount', AddNewFlagCountAttribute::class),

    (new Extend\ApiSerializer(ForumSerializer::class))
        ->attributes(AddFlagsApiAttributes::class),

    (new Extend\ApiController(ShowDiscussionController::class))
        ->addInclude(['posts.flags', 'posts.flags.user']),

    (new Extend\ApiController(ListPostsController::class))
        ->addInclude(['flags', 'flags.user']),

    (new Extend\ApiController(ShowPostController::class))
        ->addInclude(['flags', 'flags.user']),

    (new Extend\ApiController(AbstractSerializeController::class))
        ->prepareDataForSerialization(PrepareFlagsApiData::class),

    (new Extend\Settings())
        ->serializeToForum('guidelinesUrl', 'flarum-flags.guidelines_url'),

    (new Extend\Event())
        ->listen(Deleted::class, Listener\DeleteFlags::class),

    new Extend\Locales(__DIR__.'/locale'),
];
