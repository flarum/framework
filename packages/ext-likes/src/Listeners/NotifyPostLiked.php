<?php namespace Flarum\Likes\Listeners;

use Flarum\Likes\Notifications\PostLikedBlueprint;
use Flarum\Events\RegisterNotificationTypes;
use Flarum\Likes\Events\PostWasLiked;
use Flarum\Likes\Events\PostWasUnliked;
use Flarum\Core\Posts\Post;
use Flarum\Core\Users\User;
use Flarum\Core\Notifications\NotificationSyncer;
use Illuminate\Contracts\Events\Dispatcher;

class NotifyPostLiked
{
    protected $notifications;

    public function __construct(NotificationSyncer $notifications)
    {
        $this->notifications = $notifications;
    }

    public function subscribe(Dispatcher $events)
    {
        $events->listen(RegisterNotificationTypes::class, __CLASS__.'@registerNotificationType');
        $events->listen(PostWasLiked::class, __CLASS__.'@whenPostWasLiked');
        $events->listen(PostWasUnliked::class, __CLASS__.'@whenPostWasUnliked');
    }

    public function registerNotificationType(RegisterNotificationTypes $event)
    {
        $event->register(
            'Flarum\Likes\Notifications\PostLikedBlueprint',
            'Flarum\Api\Serializers\PostBasicSerializer',
            ['alert']
        );
    }

    public function whenPostWasLiked(PostWasLiked $event)
    {
        $this->sync($event->post, $event->user, [$event->post->user]);
    }

    public function whenPostWasUnliked(PostWasUnliked $event)
    {
        $this->sync($event->post, $event->user, []);
    }

    public function sync(Post $post, User $user, array $recipients)
    {
        if ($post->user->id != $user->id) {
            $this->notifications->sync(
                new PostLikedBlueprint($post, $user),
                $recipients
            );
        }
    }
}
