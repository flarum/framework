<?php namespace Flarum\Events;

use s9e\TextFormatter\Renderer;
use Flarum\Core\Posts\CommentPost;

class FormatterRenderer
{
    /**
     * @var Renderer
     */
    public $renderer;

    /**
     * @var CommentPost
     */
    public $post;

    /**
     * @param Renderer $renderer
     * @param CommentPost $post
     */
    public function __construct(Renderer $renderer, CommentPost $post)
    {
        $this->renderer = $renderer;
        $this->post = $post;
    }
}
