<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Api\Event\WillSerializeData;
use Flarum\Api\Serializer\PostSerializer;
use Flarum\Event\ConfigurePostsQuery;
use Flarum\Extend;
use Flarum\Formatter\Event\Rendering;
use Flarum\Mentions\ConfigureMentions;
use Flarum\Mentions\Listener;
use Flarum\Mentions\Notification\PostMentionedBlueprint;
use Flarum\Mentions\Notification\UserMentionedBlueprint;
use Flarum\Post\Event\Deleted;
use Flarum\Post\Event\Hidden;
use Flarum\Post\Event\Posted;
use Flarum\Post\Event\Restored;
use Flarum\Post\Event\Revised;
use Flarum\Post\Post;
use Flarum\User\User;
use Illuminate\Contracts\Events\Dispatcher;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less'),

    (new Extend\Formatter)
        ->configure(ConfigureMentions::class),

    (new Extend\Model(Post::class))
        ->belongsToMany('mentionedBy', Post::class, 'post_mentions_post', 'mentions_post_id', 'post_id')
        ->belongsToMany('mentionsPosts', Post::class, 'post_mentions_post', 'post_id', 'mentions_post_id')
        ->belongsToMany('mentionsUsers', User::class, 'post_mentions_user', 'post_id', 'mentions_user_id'),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\View)
        ->namespace('flarum-mentions', __DIR__.'/views'),

    (new Extend\Notification())
        ->type(PostMentionedBlueprint::class, PostSerializer::class, ['alert'])
        ->type(UserMentionedBlueprint::class, PostSerializer::class, ['alert']),

    function (Dispatcher $events) {
        $events->listen(WillSerializeData::class, Listener\FilterVisiblePosts::class);
        $events->subscribe(Listener\AddPostMentionedByRelationship::class);

        $events->listen(
            [Posted::class, Restored::class, Revised::class],
            Listener\UpdateMentionsMetadataWhenVisible::class
        );
        $events->listen(
            [Deleted::class, Hidden::class],
            Listener\UpdateMentionsMetadataWhenInvisible::class
        );

        $events->listen(ConfigurePostsQuery::class, Listener\AddFilterByMentions::class);

        $events->listen(Rendering::class, Listener\FormatPostMentions::class);
        $events->listen(Rendering::class, Listener\FormatUserMentions::class);
    },
];
