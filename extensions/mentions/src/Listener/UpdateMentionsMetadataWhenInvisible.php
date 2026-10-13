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
        // Remove user mentions. A deleted post's rows are already gone (they
        // cascade), so its mentioned users come from the relation loaded while
        // it was being deleted.
        $mentioned = $event->post->relationLoaded('mentionsUsers')
            ? $event->post->mentionsUsers->pluck('id')->all()
            : [];

        $changes = $event->post->mentionsUsers()->sync([]);
        $this->notifications->sync(new UserMentionedBlueprint($event->post), []);

        $this->mentionCount->refresh(array_merge($mentioned, $changes['detached']));

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
