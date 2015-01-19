<?php namespace Flarum\Core\Listeners;

use Laracasts\Commander\Events\EventListener;

use Flarum\Core\Discussions\DiscussionRepository;
use Flarum\Core\Posts\Post;
use Flarum\Core\Posts\Events\ReplyWasPosted;
use Flarum\Core\Posts\Events\PostWasDeleted;
use Flarum\Core\Posts\Events\PostWasHidden;
use Flarum\Core\Posts\Events\PostWasRestored;

class DiscussionMetadataUpdater extends EventListener
{
    protected $discussionRepo;

    public function __construct(DiscussionRepository $discussionRepo)
    {
        $this->discussionRepo = $discussionRepo;
    }

    public function whenReplyWasPosted(ReplyWasPosted $event)
    {
        $discussion = $this->discussionRepo->find($event->post->discussion_id);

        $discussion->comments_count++;
        $discussion->setLastPost($event->post);

        $this->discussionRepo->save($discussion);
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
        $discussion = $this->discussionRepo->find($event->post->discussion_id);

        $discussion->comments_count++;
        $discussion->refreshLastPost();

        $this->discussionRepo->save($discussion);
    }

    protected function removePost(Post $post)
    {
        $discussion = $this->discussionRepo->find($post->discussion_id);

        $discussion->comments_count--;

        if ($discussion->last_post_id == $post->id) {
            $discussion->refreshLastPost();
        }

        $this->discussionRepo->save($discussion);
    }
}
