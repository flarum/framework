<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Api\Context;
use Flarum\Api\Resource;
use Flarum\Api\Schema;
use Flarum\Discussion\Discussion;
use Flarum\Discussion\Search\DiscussionSearcher;
use Flarum\Extend;
use Flarum\Lock\Access;
use Flarum\Lock\Event\DiscussionWasLocked;
use Flarum\Lock\Event\DiscussionWasUnlocked;
use Flarum\Lock\Filter\LockedFilter;
use Flarum\Lock\Listener;
use Flarum\Lock\Notification\DiscussionLockedBlueprint;
use Flarum\Lock\Post\DiscussionLockedPost;
use Flarum\Search\Database\DatabaseSearchDriver;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\Notification())
        ->type(DiscussionLockedBlueprint::class, ['alert']),

    (new Extend\Model(Discussion::class))
        ->cast('is_locked', 'bool'),

    (new Extend\ApiResource(Resource\DiscussionResource::class))
        ->fields(fn () => [
            Schema\Boolean::make('isLocked')
                ->writable(fn (Discussion $discussion, Context $context) => $context->getActor()->can('lock', $discussion))
                ->set(function (Discussion $discussion, bool $isLocked, Context $context) {
                    $actor = $context->getActor();

                    if ($discussion->is_locked === $isLocked) {
                        return;
                    }

                    $discussion->is_locked = $isLocked;

                    $discussion->raise(
                        $discussion->is_locked
                            ? new DiscussionWasLocked($discussion, $actor)
                            : new DiscussionWasUnlocked($discussion, $actor)
                    );
                }),
            Schema\Boolean::make('canLock')
                ->get(fn (Discussion $discussion, Context $context) => $context->getActor()->can('lock', $discussion)),
        ]),

    (new Extend\Post())
        ->type(DiscussionLockedPost::class),

    (new Extend\Event())
        ->listen(DiscussionWasLocked::class, Listener\CreatePostWhenDiscussionIsLocked::class)
        ->listen(DiscussionWasUnlocked::class, Listener\CreatePostWhenDiscussionIsUnlocked::class),

    (new Extend\Policy())
        ->modelPolicy(Discussion::class, Access\DiscussionPolicy::class),

    (new Extend\SearchDriver(DatabaseSearchDriver::class))
        ->addFilter(DiscussionSearcher::class, LockedFilter::class),
];
