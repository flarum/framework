<?php namespace Flarum\Core\Handlers\Events;

use Flarum\Core\Models\User;
use Flarum\Core\Events\PostWasPosted;
use Flarum\Core\Events\PostWasDeleted;
use Flarum\Core\Events\PostWasHidden;
use Flarum\Core\Events\PostWasRestored;
use Flarum\Core\Events\DiscussionWasStarted;
use Flarum\Core\Events\DiscussionWasDeleted;

class UserMetadataUpdater
{
    /**
     * Register the listeners for the subscriber.
     *
     * @param  Illuminate\Events\Dispatcher  $events
     * @return array
     */
    public function subscribe($events)
    {
        $events->listen('Flarum\Core\Events\PostWasPosted', __CLASS__.'@whenPostWasPosted');
        $events->listen('Flarum\Core\Events\PostWasDeleted', __CLASS__.'@whenPostWasDeleted');
        $events->listen('Flarum\Core\Events\PostWasHidden', __CLASS__.'@whenPostWasHidden');
        $events->listen('Flarum\Core\Events\PostWasRestored', __CLASS__.'@whenPostWasRestored');
        $events->listen('Flarum\Core\Events\DiscussionWasStarted', __CLASS__.'@whenDiscussionWasStarted');
        $events->listen('Flarum\Core\Events\DiscussionWasDeleted', __CLASS__.'@whenDiscussionWasDeleted');
    }

    public function whenPostWasPosted(PostWasPosted $event)
    {
        $this->updateRepliesCount($event->post->user, 1);
    }

    public function whenPostWasDeleted(PostWasDeleted $event)
    {
        $this->updateRepliesCount($event->post->user, -1);
    }

    public function whenPostWasHidden(PostWasHidden $event)
    {
        $this->updateRepliesCount($event->post->user, -1);
    }

    public function whenPostWasRestored(PostWasRestored $event)
    {
        $this->updateRepliesCount($event->post->user, 1);
    }

    public function whenDiscussionWasStarted(DiscussionWasStarted $event)
    {
        $this->updateDiscussionsCount($event->discussion->startUser, 1);
    }

    public function whenDiscussionWasDeleted(DiscussionWasDeleted $event)
    {
        $this->updateDiscussionsCount($event->discussion->startUser, -1);
    }

    protected function updateRepliesCount(User $user, $amount)
    {
        $user->posts_count += $amount;
        $user->save();
    }

    protected function updateDiscussionsCount(User $user, $amount)
    {
        $user->discussions_count += $amount;
        $user->save();
    }
}
