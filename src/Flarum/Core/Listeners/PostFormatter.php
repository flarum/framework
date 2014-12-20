<?php namespace Flarum\Core\Listeners;

use Laracasts\Commander\Events\EventListener;

use Flarum\Core\Posts\PostRepository;
use Flarum\Core\Posts\Post;
use Flarum\Core\Posts\Events\ReplyWasPosted;
use Flarum\Core\Posts\Events\PostWasRevised;

class PostFormatter extends EventListener
{
    protected $postRepo;

    public function __construct(PostRepository $postRepo)
    {
        $this->postRepo = $postRepo;
    }

    protected function formatPost($post)
    {
        $post = $this->postRepo->find($post->id);

        // By default, we want to convert paragraphs of text into <p> tags.
        // And maybe also wrap URLs in <a> tags.
        // However, we want to allow plugins to completely override this, and/or
        // just do some superficial formatting afterwards.

        $html = htmlspecialchars($post->content);

        // Primary formatter
        $html = '<p>'.$html.'</p>'; // Move this to Flarum\Core\Support\Formatters\BasicFormatter < FormatterInterface

        // Run additional formatters
        
        $post->content_html = $html;
        $this->postRepo->save($post);
    }

    public function whenReplyWasPosted(ReplyWasPosted $event)
    {
        $this->formatPost($event->post);
    }

    public function whenPostWasRevised(PostWasRevised $event)
    {
        $this->formatPost($event->post);
    }
}
