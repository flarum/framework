<?php namespace Flarum\Core\Listeners;

use Laracasts\Commander\Events\EventListener;

use Flarum\Core\Posts\PostRepository;
use Flarum\Core\Posts\RenamedPost;
use Flarum\Core\Discussions\Events\DiscussionWasRenamed;

class RenamedPostCreator extends EventListener
{
    protected $postRepo;

    public function __construct(PostRepository $postRepo)
    {
        $this->postRepo = $postRepo;
    }

    public function whenDiscussionWasRenamed(DiscussionWasRenamed $event)
    {
        $post = RenamedPost::reply(
            $event->discussion->id,
            $event->user->id,
            $event->oldTitle,
            $event->discussion->title
        );

        $this->postRepo->save($post);

        $event->discussion->postWasAdded($post);
    }
}
