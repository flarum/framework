<?php namespace Flarum\Mentions;

use Flarum\Core\Formatter\FormatterAbstract;
use Flarum\Core\Models\Post;

class PostMentionsFormatter extends FormatterAbstract
{
    protected $parser;

    public function __construct(PostMentionsParser $parser)
    {
        $this->parser = $parser;
    }

    public function afterPurification($text, Post $post = null)
    {
        if ($post) {
            $text = $this->ignoreTags($text, ['a', 'code', 'pre'], function ($text) use ($post) {
                return $this->parser->replace($text, function ($match) use ($post) {
                    // TODO: use URL generator
                    return '<a href="/d/'.$post->discussion_id.'/-/'.$match['number'].'" class="mention-post" data-number="'.$match['number'].'">'.$match['username'].'</a>';
                }, $text);
            });
        }

        return $text;
    }
}
