<?php namespace Flarum\Core\Listeners;

use Laracasts\Commander\Events\EventListener;

use Flarum\Core\Posts\PostRepository;
use Flarum\Core\Posts\TitleChangePost;
use Flarum\Core\Discussions\Events\DiscussionWasRenamed;

class TitleChangePostCreator extends EventListener
{
    protected $postRepo;

    public function __construct(PostRepository $postRepo)
    {
        $this->postRepo = $postRepo;
    }

    public function whenDiscussionWasRenamed(DiscussionWasRenamed $event)
    {
        $post = TitleChangePost::reply(
            $event->discussion->id,
            $event->discussion->title,
            $event->user->id
        );

        $this->postRepo->save($post);
    }
}
