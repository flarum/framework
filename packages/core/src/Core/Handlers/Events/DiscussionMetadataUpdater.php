<?php namespace Flarum\Core\Handlers\Events;

use Flarum\Core\Models\Post;
use Flarum\Core\Events\PostWasPosted;
use Flarum\Core\Events\PostWasDeleted;
use Flarum\Core\Events\PostWasHidden;
use Flarum\Core\Events\PostWasRestored;
use Illuminate\Contracts\Events\Dispatcher;

class DiscussionMetadataUpdater
{
    /**
     * Register the listeners for the subscriber.
     *
     * @param \Illuminate\Contracts\Events\Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen('Flarum\Core\Events\PostWasPosted', __CLASS__.'@whenPostWasPosted');
        $events->listen('Flarum\Core\Events\PostWasDeleted', __CLASS__.'@whenPostWasDeleted');
        $events->listen('Flarum\Core\Events\PostWasHidden', __CLASS__.'@whenPostWasHidden');
        $events->listen('Flarum\Core\Events\PostWasRestored', __CLASS__.'@whenPostWasRestored');
    }

    public function whenPostWasPosted(PostWasPosted $event)
    {
        $discussion = $event->post->discussion;

        $discussion->comments_count++;
        $discussion->setLastPost($event->post);
        $discussion->save();
    }

    public function whenPostWasDeleted(PostWasDeleted $event)
    {
        $this->removePost($event->post);
    }

    public function whenPostWasHidden(PostWasHidden $event)
    {
        $this->removePost($event->post);
    }

    public function whenPostWasRestored(PostWasRestored $event)
    {
        $discussion = $event->post->discussion;

        $discussion->refreshCommentsCount();
        $discussion->refreshLastPost();
        $discussion->save();
    }

    protected function removePost(Post $post)
    {
        $discussion = $post->discussion;

        $discussion->refreshCommentsCount();

        if ($discussion->last_post_id == $post->id) {
            $discussion->refreshLastPost();
        }

        $discussion->save();
    }
}
