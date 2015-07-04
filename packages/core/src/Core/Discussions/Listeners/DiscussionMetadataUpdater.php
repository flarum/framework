<?php namespace Flarum\Core\Discussions\Listeners;

use Flarum\Core\Posts\Post;
use Flarum\Core\Posts\Events\PostWasPosted;
use Flarum\Core\Posts\Events\PostWasDeleted;
use Flarum\Core\Posts\Events\PostWasHidden;
use Flarum\Core\Posts\Events\PostWasRestored;
use Illuminate\Contracts\Events\Dispatcher;

class DiscussionMetadataUpdater
{
    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(PostWasPosted::class, __CLASS__.'@whenPostWasPosted');
        $events->listen(PostWasDeleted::class, __CLASS__.'@whenPostWasDeleted');
        $events->listen(PostWasHidden::class, __CLASS__.'@whenPostWasHidden');
        $events->listen(PostWasRestored::class, __CLASS__.'@whenPostWasRestored');
    }

    /**
     * @param PostWasPosted $event
     */
    public function whenPostWasPosted(PostWasPosted $event)
    {
        $discussion = $event->post->discussion;

        $discussion->comments_count++;
        $discussion->setLastPost($event->post);
        $discussion->refreshParticipantsCount();
        $discussion->save();
    }

    /**
     * @param PostWasDeleted $event
     */
    public function whenPostWasDeleted(PostWasDeleted $event)
    {
        $this->removePost($event->post);
    }

    /**
     * @param PostWasHidden $event
     */
    public function whenPostWasHidden(PostWasHidden $event)
    {
        $this->removePost($event->post);
    }

    /**
     * @param PostWasRestored $event
     */
    public function whenPostWasRestored(PostWasRestored $event)
    {
        $discussion = $event->post->discussion;

        $discussion->refreshCommentsCount();
        $discussion->refreshParticipantsCount();
        $discussion->refreshLastPost();
        $discussion->save();
    }

    /**
     * @param Post $post
     */
    protected function removePost(Post $post)
    {
        $discussion = $post->discussion;

        if ($discussion->exists) {
            $discussion->refreshCommentsCount();
            $discussion->refreshParticipantsCount();

            if ($discussion->last_post_id == $post->id) {
                $discussion->refreshLastPost();
            }

            $discussion->save();
        }
    }
}
