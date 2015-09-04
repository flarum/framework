<?php 
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Subscriptions\Listeners;

use Flarum\Subscriptions\Notifications\NewPostBlueprint;
use Flarum\Events\RegisterNotificationTypes;
use Flarum\Events\PostWasPosted;
use Flarum\Events\PostWasHidden;
use Flarum\Events\PostWasRestored;
use Flarum\Events\PostWasDeleted;
use Flarum\Core\Notifications\NotificationSyncer;
use Illuminate\Contracts\Events\Dispatcher;

class NotifyNewPosts
{
    protected $notifications;

    public function __construct(NotificationSyncer $notifications)
    {
        $this->notifications = $notifications;
    }

    public function subscribe(Dispatcher $events)
    {
        $events->listen(RegisterNotificationTypes::class, [$this, 'addNotificationType']);

        // Register with '1' as priority so this runs before discussion metadata
        // is updated, as we need to compare the user's last read number to that
        // of the previous post.
        $events->listen(PostWasPosted::class, [$this, 'whenPostWasPosted'], 1);
        $events->listen(PostWasHidden::class, [$this, 'whenPostWasHidden']);
        $events->listen(PostWasRestored::class, [$this, 'whenPostWasRestored']);
        $events->listen(PostWasDeleted::class, [$this, 'whenPostWasDeleted']);
    }

    public function addNotificationType(RegisterNotificationTypes $event)
    {
        $event->register(
            NewPostBlueprint::class,
            'Flarum\Api\Serializers\DiscussionBasicSerializer',
            ['alert', 'email']
        );
    }

    public function whenPostWasPosted(PostWasPosted $event)
    {
        $post = $event->post;
        $discussion = $post->discussion;

        $notify = $discussion->readers()
            ->where('users.id', '!=', $post->user_id)
            ->where('users_discussions.subscription', 'follow')
            ->where('users_discussions.read_number', $discussion->last_post_number)
            ->get();

        $this->notifications->sync(
            $this->getNotification($event->post),
            $notify->all()
        );
    }

    public function whenPostWasHidden(PostWasHidden $event)
    {
        $this->notifications->delete($this->getNotification($event->post));
    }

    public function whenPostWasRestored(PostWasRestored $event)
    {
        $this->notifications->restore($this->getNotification($event->post));
    }

    public function whenPostWasDeleted(PostWasDeleted $event)
    {
        $this->notifications->delete($this->getNotification($event->post));
    }

    protected function getNotification($post)
    {
        return new NewPostBlueprint($post);
    }
}
