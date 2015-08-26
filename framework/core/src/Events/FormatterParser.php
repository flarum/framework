<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Events;

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
