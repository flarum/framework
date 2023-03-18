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
    /**
     * @var NotificationSyncer
     */
    protected $notifications;

    /**
     * @var ExtensionManager
     */
    protected $extensions;

    public function __construct(NotificationSyncer $notifications, ExtensionManager $extensions)
    {
        $this->notifications = $notifications;
        $this->extensions = $extensions;
    }

    /**
     * @param Deleted|Hidden $event
     */
    public function handle($event)
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
