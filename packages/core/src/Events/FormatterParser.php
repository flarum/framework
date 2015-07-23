<?php namespace Flarum\Events;

use s9e\TextFormatter\Parser;
use Flarum\Core\Posts\CommentPost;

class FormatterParser
{
    /**
     * @var Parser
     */
    public $parser;

    /**
     * @var CommentPost
     */
    public $post;

    /**
     * @param Parser $parser
     * @param CommentPost $post
     */
    public function __construct(Parser $parser, CommentPost $post)
    {
        $this->parser = $parser;
        $this->post = $post;
    }
}
