<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Api\Resource;
use Flarum\Api\Schema;
use Flarum\Approval\Access;
use Flarum\Approval\Api\PostResourceFields;
use Flarum\Approval\Event\PostWasApproved;
use Flarum\Approval\Listener;
use Flarum\Discussion\Discussion;
use Flarum\Extend;
use Flarum\Post\CommentPost;
use Flarum\Post\Post;
use Flarum\Tags\Tag;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),

    // Discussions should be approved by default
    (new Extend\Model(Discussion::class))
        ->default('is_approved', true)
        ->cast('is_approved', 'bool'),

    // Posts should be approved by default
    (new Extend\Model(Post::class))
        ->default('is_approved', true)
        ->cast('is_approved', 'bool'),

    (new Extend\ApiResource(Resource\DiscussionResource::class))
        ->fields(fn () => [
            Schema\Boolean::make('isApproved'),
        ]),

    (new Extend\ApiResource(Resource\PostResource::class))
        ->fields(PostResourceFields::class),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\Event())
        ->listen(PostWasApproved::class, Listener\UpdateDiscussionAfterPostApproval::class)
        ->subscribe(Listener\ApproveContent::class)
        ->subscribe(Listener\UnapproveNewContent::class),

    (new Extend\Policy())
        ->modelPolicy(Tag::class, Access\TagPolicy::class),

    (new Extend\ModelVisibility(Post::class))
        ->scope(Access\ScopePrivatePostVisibility::class, 'viewPrivate'),

    (new Extend\ModelVisibility(Discussion::class))
        ->scope(Access\ScopePrivateDiscussionVisibility::class, 'viewPrivate'),

    (new Extend\ModelPrivate(Discussion::class))
        ->checker(Listener\UnapproveNewContent::markUnapprovedContentAsPrivate(...)),

    (new Extend\ModelPrivate(CommentPost::class))
        ->checker(Listener\UnapproveNewContent::markUnapprovedContentAsPrivate(...)),
];
