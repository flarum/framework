<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Listener;

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
     * @param NotificationSyncer $notifications
     */
    public function __construct(NotificationSyncer $notifications)
    {
        $this->notifications = $notifications;
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
    }
}
