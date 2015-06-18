<?php namespace Flarum\Core\Handlers\Events;

use Flarum\Core\Activity\ActivitySyncer;
use Flarum\Core\Activity\PostedActivity;
use Flarum\Core\Activity\StartedDiscussionActivity;
use Flarum\Core\Activity\JoinedActivity;
use Flarum\Core\Events\PostWasPosted;
use Flarum\Core\Events\PostWasDeleted;
use Flarum\Core\Events\PostWasHidden;
use Flarum\Core\Events\PostWasRestored;
use Flarum\Core\Events\UserWasRegistered;
use Flarum\Core\Models\Post;
use Illuminate\Contracts\Events\Dispatcher;

class UserActivitySyncer
{
    protected $activity;

    public function __construct(ActivitySyncer $activity)
    {
        $this->activity = $activity;
    }

    public function subscribe(Dispatcher $events)
    {
        $events->listen('Flarum\Core\Events\PostWasPosted', __CLASS__.'@whenPostWasPosted');
        $events->listen('Flarum\Core\Events\PostWasHidden', __CLASS__.'@whenPostWasHidden');
        $events->listen('Flarum\Core\Events\PostWasRestored', __CLASS__.'@whenPostWasRestored');
        $events->listen('Flarum\Core\Events\PostWasDeleted', __CLASS__.'@whenPostWasDeleted');
        $events->listen('Flarum\Core\Events\UserWasRegistered', __CLASS__.'@whenUserWasRegistered');
    }

    public function whenPostWasPosted(PostWasPosted $event)
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

    public function whenUserWasRegistered(UserWasRegistered $event)
    {
        $this->activity->sync(new JoinedActivity($event->user), [$event->user]);
    }

    protected function postBecameVisible(Post $post)
    {
        $activity = $this->postedActivity($post);

        $this->activity->sync($activity, [$post->user]);
    }

    protected function postBecameInvisible(Post $post)
    {
        $activity = $this->postedActivity($post);

        $this->activity->sync($activity, []);
    }

    protected function postedActivity(Post $post)
    {
        return $post->number == 1 ? new StartedDiscussionActivity($post) : new PostedActivity($post);
    }
}
