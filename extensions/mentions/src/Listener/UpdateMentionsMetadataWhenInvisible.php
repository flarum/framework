<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Listener;

use Flarum\Extension\ExtensionManager;
use Flarum\Mentions\Notification\UserMentionedBlueprint;
use Flarum\Mentions\UserMentionCount;
use Flarum\Notification\NotificationSyncer;
use Flarum\Post\Event\Deleted;
use Flarum\Post\Event\Hidden;
use Flarum\User\User;

class UpdateMentionsMetadataWhenInvisible
{
    public function __construct(
        protected NotificationSyncer $notifications,
        protected ExtensionManager $extensions,
        protected UserMentionCount $mentionCount
    ) {
    }

    public function handle(Deleted|Hidden $event): void
    {
        // A deleted post's mention rows are normally already gone (they cascade
        // with it), and UpdateMentionCountWhenPostsDeleted recounted the users
        // they named from the ids it read first. Without cascading foreign keys
        // (SQLite installs) rows can be left behind, and detaching finds them.
        $changes = $event->post->mentionsUsers()->sync([]);
        $this->notifications->sync(new UserMentionedBlueprint($event->post), []);

        $affected = $changes['detached'];
        $this->mentionCount->refresh($affected);

        // A hiding request's response reports the users whose count changed so
        // the forum can refresh them. A deletion has no response to fill.
        if ($event instanceof Hidden && $affected && $event->actor) {
            $event->post->setRelation('affectedMentionUsers', User::query()
                ->whereKey($affected)
                ->whereVisibleTo($event->actor)
                ->with('groups')
                ->get());
        }

        // Remove post mentions
        $event->post->mentionsPosts()->sync([]);

        // Remove group mentions
        $event->post->mentionsGroups()->sync([]);

        // Remove tag mentions
        if ($this->extensions->isEnabled('flarum-tags')) {
            $event->post->mentionsTags()->sync([]);
        }
    }
}
