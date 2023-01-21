<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Api\Serializer\BasicDiscussionSerializer;
use Flarum\Api\Serializer\DiscussionSerializer;
use Flarum\Discussion\Discussion;
use Flarum\Discussion\Event\Saving;
use Flarum\Discussion\Filter\DiscussionFilterer;
use Flarum\Discussion\Search\DiscussionSearcher;
use Flarum\Extend;
use Flarum\Lock\Access;
use Flarum\Lock\Event\DiscussionWasLocked;
use Flarum\Lock\Event\DiscussionWasUnlocked;
use Flarum\Lock\Listener;
use Flarum\Lock\Notification\DiscussionLockedBlueprint;
use Flarum\Lock\Post\DiscussionLockedPost;
use Flarum\Lock\Query\LockedFilterGambit;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\Notification())
        ->type(DiscussionLockedBlueprint::class, BasicDiscussionSerializer::class, ['alert']),

    (new Extend\Model(Discussion::class))
        ->cast('is_locked', 'bool'),

    (new Extend\ApiSerializer(DiscussionSerializer::class))
        ->attribute('isLocked', function (DiscussionSerializer $serializer, Discussion $discussion) {
            return $discussion->is_locked;
        })
        ->attribute('canLock', function (DiscussionSerializer $serializer, Discussion $discussion) {
            return $serializer->getActor()->can('lock', $discussion);
        }),

    (new Extend\Post())
        ->type(DiscussionLockedPost::class),

    (new Extend\Event())
        ->listen(Saving::class, Listener\SaveLockedToDatabase::class)
        ->listen(DiscussionWasLocked::class, Listener\CreatePostWhenDiscussionIsLocked::class)
        ->listen(DiscussionWasUnlocked::class, Listener\CreatePostWhenDiscussionIsUnlocked::class),

    (new Extend\Policy())
        ->modelPolicy(Discussion::class, Access\DiscussionPolicy::class),

    (new Extend\Filter(DiscussionFilterer::class))
        ->addFilter(LockedFilterGambit::class),

    (new Extend\SimpleFlarumSearch(DiscussionSearcher::class))
        ->addGambit(LockedFilterGambit::class),
];
