<?php namespace Flarum\Core\Activity\Listeners;

use Flarum\Core\Activity\ActivitySyncer;
use Flarum\Core\Activity\PostedBlueprint;
use Flarum\Core\Activity\StartedDiscussionBlueprint;
use Flarum\Core\Activity\JoinedBlueprint;
use Flarum\Core\Posts\Post;
use Flarum\Events\PostWasPosted;
use Flarum\Events\PostWasDeleted;
use Flarum\Events\PostWasHidden;
use Flarum\Events\PostWasRestored;
use Flarum\Events\UserWasRegistered;
use Illuminate\Contracts\Events\Dispatcher;

class UserActivitySyncer
{
    /**
     * @var \Flarum\Core\Activity\ActivitySyncer
     */
    protected $activity;

    /**
     * @param \Flarum\Core\Activity\ActivitySyncer $activity
     */
    public function __construct(ActivitySyncer $activity)
    {
        $this->activity = $activity;
    }

    /**
     * @param \Illuminate\Contracts\Events\Dispatcher $events
     * @return void
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen('Flarum\Events\PostWasPosted', __CLASS__.'@whenPostWasPosted');
        $events->listen('Flarum\Events\PostWasHidden', __CLASS__.'@whenPostWasHidden');
        $events->listen('Flarum\Events\PostWasRestored', __CLASS__.'@whenPostWasRestored');
        $events->listen('Flarum\Events\PostWasDeleted', __CLASS__.'@whenPostWasDeleted');
        $events->listen('Flarum\Events\UserWasRegistered', __CLASS__.'@whenUserWasRegistered');
    }

    /**
     * @param \Flarum\Events\PostWasPosted $event
     * @return void
     */
    public function whenPostWasPosted(PostWasPosted $event)
    {
        $this->postBecameVisible($event->post);
    }

    /**
     * @param \Flarum\Events\PostWasHidden $event
     * @return void
     */
    public function whenPostWasHidden(PostWasHidden $event)
    {
        $this->postBecameInvisible($event->post);
    }

    /**
     * @param \Flarum\Events\PostWasRestored $event
     * @return void
     */
    public function whenPostWasRestored(PostWasRestored $event)
    {
        $this->postBecameVisible($event->post);
    }

    /**
     * @param \Flarum\Events\PostWasDeleted $event
     * @return void
     */
    public function whenPostWasDeleted(PostWasDeleted $event)
    {
        $this->postBecameInvisible($event->post);
    }

    /**
     * @param \Flarum\Events\UserWasRegistered $event
     * @return void
     */
    public function whenUserWasRegistered(UserWasRegistered $event)
    {
        $blueprint = new JoinedBlueprint($event->user);

        $this->activity->sync($blueprint, [$event->user]);
    }

    /**
     * Sync activity to a post's author when a post becomes visible.
     *
     * @param \Flarum\Core\Posts\Post $post
     * @return void
     */
    protected function postBecameVisible(Post $post)
    {
        $blueprint = $this->postedBlueprint($post);

        $this->activity->sync($blueprint, [$post->user]);
    }

    /**
     * Delete activity when a post becomes invisible.
     *
     * @param \Flarum\Core\Posts\Post $post
     * @return void
     */
    protected function postBecameInvisible(Post $post)
    {
        $blueprint = $this->postedBlueprint($post);

        $this->activity->delete($blueprint);
    }

    /**
     * Create the appropriate activity blueprint for a post.
     *
     * @param \Flarum\Core\Posts\Post $post
     * @return \Flarum\Core\Activity\Blueprint
     */
    protected function postedBlueprint(Post $post)
    {
        return $post->number == 1 ? new StartedDiscussionBlueprint($post) : new PostedBlueprint($post);
    }
}
