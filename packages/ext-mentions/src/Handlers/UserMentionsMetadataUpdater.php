<?php namespace Flarum\Mentions\Handlers;

use Flarum\Mentions\UserMentionsParser;
use Flarum\Mentions\UserMentionedActivity;
use Flarum\Mentions\UserMentionedNotification;
use Flarum\Core\Activity\ActivitySyncer;
use Flarum\Core\Notifications\NotificationSyncer;
use Flarum\Core\Events\PostWasPosted;
use Flarum\Core\Events\PostWasRevised;
use Flarum\Core\Events\PostWasDeleted;
use Flarum\Core\Events\PostWasHidden;
use Flarum\Core\Events\PostWasRestored;
use Flarum\Core\Models\Post;
use Flarum\Core\Models\User;
use Illuminate\Contracts\Events\Dispatcher;

class UserMentionsMetadataUpdater
{
    protected $parser;

    protected $activity;

    protected $notifications;

    public function __construct(UserMentionsParser $parser, ActivitySyncer $activity, NotificationSyncer $notifications)
    {
        $this->parser = $parser;
        $this->activity = $activity;
        $this->notifications = $notifications;
    }

    public function subscribe(Dispatcher $events)
    {
        $events->listen('Flarum\Core\Events\PostWasPosted', __CLASS__.'@whenPostWasPosted');
        $events->listen('Flarum\Core\Events\PostWasRevised', __CLASS__.'@whenPostWasRevised');
        $events->listen('Flarum\Core\Events\PostWasHidden', __CLASS__.'@whenPostWasHidden');
        $events->listen('Flarum\Core\Events\PostWasRestored', __CLASS__.'@whenPostWasRestored');
        $events->listen('Flarum\Core\Events\PostWasDeleted', __CLASS__.'@whenPostWasDeleted');
    }

    public function whenPostWasPosted(PostWasPosted $event)
    {
        $this->postBecameVisible($event->post);
    }

    public function whenPostWasRevised(PostWasRevised $event)
    {
        $this->postBecameVisible($event->post);
    }

    public function whenPostWasHidden(PostWasHidden $event)
    {
        $this->postBecameInvisible($event->post);
    }

    public function whenPostWasRestored(PostWasRestored $event)
    {
        $this->postBecameVisible($event->post);
    }

    public function whenPostWasDeleted(PostWasDeleted $event)
    {
        $this->postBecameInvisible($event->post);
    }

    protected function postBecameVisible(Post $post)
    {
        $matches = $this->parser->match($post->content);

        $mentioned = User::whereIn('username', array_filter($matches['username']))->get()->all();

        $this->sync($post, $mentioned);
    }

    protected function postBecameInvisible(Post $post)
    {
        $this->sync($post, []);
    }

    protected function sync(Post $post, array $mentioned)
    {
        $post->mentionsUsers()->sync(array_pluck($mentioned, 'id'));

        $mentioned = array_filter($mentioned, function ($user) use ($post) {
            return $user->id !== $post->user->id;
        });

        $this->activity->sync(new UserMentionedActivity($post), $mentioned);

        $this->notifications->sync(new UserMentionedNotification($post), $mentioned);
    }
}
