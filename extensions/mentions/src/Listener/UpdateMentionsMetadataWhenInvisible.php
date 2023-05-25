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
use Flarum\Notification\NotificationSyncer;
use Flarum\Post\Event\Deleted;
use Flarum\Post\Event\Hidden;

class UpdateMentionsMetadataWhenInvisible
{
    public function __construct(
        protected NotificationSyncer $notifications,
        protected ExtensionManager $extensions
    ) {
    }

    public function handle(Deleted|Hidden $event): void
    {
        // Remove user mentions
        $event->post->mentionsUsers()->sync([]);
        $this->notifications->sync(new UserMentionedBlueprint($event->post), []);

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
