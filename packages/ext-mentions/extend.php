<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Flarum\Api\Event\WillSerializeData;
use Flarum\Api\Serializer\PostSerializer;
use Flarum\Event\ConfigureNotificationTypes;
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
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\View\Factory;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less'),

    (new Extend\Formatter)
        ->configure(ConfigureMentions::class),

    function (Dispatcher $events, Factory $views) {
        $events->listen(WillSerializeData::class, Listener\FilterVisiblePosts::class);
        $events->subscribe(Listener\AddPostMentionedByRelationship::class);

        $events->listen(ConfigureNotificationTypes::class, function (ConfigureNotificationTypes $event) {
            $event->add(PostMentionedBlueprint::class, PostSerializer::class, ['alert']);
            $event->add(UserMentionedBlueprint::class, PostSerializer::class, ['alert']);
        });
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

        $views->addNamespace('flarum-mentions', __DIR__.'/views');
    },
];
