<?php 
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Sticky\Listeners;

use Flarum\Events\RegisterPostTypes;
use Flarum\Events\RegisterNotificationTypes;
use Flarum\Sticky\Posts\DiscussionStickiedPost;
use Flarum\Sticky\Notifications\DiscussionStickiedBlueprint;
use Flarum\Sticky\Events\DiscussionWasStickied;
use Flarum\Sticky\Events\DiscussionWasUnstickied;
use Flarum\Core\Notifications\NotificationSyncer;
use Flarum\Core\Discussions\Discussion;
use Flarum\Core\Users\User;
use Illuminate\Contracts\Events\Dispatcher;

class NotifyDiscussionStickied
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
        $events->listen(DiscussionWasStickied::class, [$this, 'whenDiscussionWasStickied']);
        $events->listen(DiscussionWasUnstickied::class, [$this, 'whenDiscussionWasUnstickied']);
    }

    public function registerPostType(RegisterPostTypes $event)
    {
        $event->register('Flarum\Sticky\Posts\DiscussionStickiedPost');
    }

    public function registerNotificationType(RegisterNotificationTypes $event)
    {
        $event->register(
            'Flarum\Sticky\Notifications\DiscussionStickiedBlueprint',
            'Flarum\Api\Serializers\DiscussionBasicSerializer',
            ['alert']
        );
    }

    public function whenDiscussionWasStickied(DiscussionWasStickied $event)
    {
        $this->stickyChanged($event->discussion, $event->user, true);
    }

    public function whenDiscussionWasUnstickied(DiscussionWasUnstickied $event)
    {
        $this->stickyChanged($event->discussion, $event->user, false);
    }

    protected function stickyChanged(Discussion $discussion, User $user, $isSticky)
    {
        $post = DiscussionStickiedPost::reply(
            $discussion->id,
            $user->id,
            $isSticky
        );

        $post = $discussion->mergePost($post);

        if ($discussion->start_user_id !== $user->id) {
            $notification = new DiscussionStickiedBlueprint($post);

            $this->notifications->sync($notification, $post->exists ? [$discussion->startUser] : []);
        }
    }
}
