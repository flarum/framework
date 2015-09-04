<?php 
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Lock\Listeners;

use Flarum\Events\RegisterPostTypes;
use Flarum\Events\RegisterNotificationTypes;
use Flarum\Lock\Posts\DiscussionLockedPost;
use Flarum\Lock\Notifications\DiscussionLockedBlueprint;
use Flarum\Lock\Events\DiscussionWasLocked;
use Flarum\Lock\Events\DiscussionWasUnlocked;
use Flarum\Core\Notifications\NotificationSyncer;
use Flarum\Core\Discussions\Discussion;
use Flarum\Core\Users\User;
use Illuminate\Contracts\Events\Dispatcher;

class NotifyDiscussionLocked
{
    protected $notifications;

    public function __construct(NotificationSyncer $notifications)
    {
        $this->notifications = $notifications;
    }

    public function subscribe(Dispatcher $events)
    {
        $events->listen(RegisterPostTypes::class, [$this, 'registerPostType']);
        $events->listen(RegisterNotificationTypes::class, [$this, 'registerNotificationType']);
        $events->listen(DiscussionWasLocked::class, [$this, 'whenDiscussionWasLocked']);
        $events->listen(DiscussionWasUnlocked::class, [$this, 'whenDiscussionWasUnlocked']);
    }

    public function registerPostType(RegisterPostTypes $event)
    {
        $event->register('Flarum\Lock\Posts\DiscussionLockedPost');
    }

    public function registerNotificationType(RegisterNotificationTypes $event)
    {
        $event->register(
            'Flarum\Lock\Notifications\DiscussionLockedBlueprint',
            'Flarum\Api\Serializers\DiscussionBasicSerializer',
            ['alert']
        );
    }

    public function whenDiscussionWasLocked(DiscussionWasLocked $event)
    {
        $this->stickyChanged($event->discussion, $event->user, true);
    }

    public function whenDiscussionWasUnlocked(DiscussionWasUnlocked $event)
    {
        $this->stickyChanged($event->discussion, $event->user, false);
    }

    protected function stickyChanged(Discussion $discussion, User $user, $isLocked)
    {
        $post = DiscussionLockedPost::reply(
            $discussion->id,
            $user->id,
            $isLocked
        );

        $post = $discussion->mergePost($post);

        if ($discussion->start_user_id !== $user->id) {
            $notification = new DiscussionLockedBlueprint($post);

            $this->notifications->sync($notification, $post->exists ? [$discussion->startUser] : []);
        }
    }
}
