<?php namespace Flarum\Mentions;

class PostMentionsFormatter
{
    protected $parser;

    public function __construct(PostMentionsParser $parser)
    {
        $this->parser = $parser;
    }

    public function format($text, $post = null)
    {
        if ($post) {
            $text = $this->parser->replace($text, function ($match) use ($post) {
                return '<a href="#/d/'.$post->discussion_id.'/-/'.$match['number'].'" class="mention-post" data-number="'.$match['number'].'">'.$match['username'].'</a>';
            }, $text);
        }

        return $text;
    }

    public function strip($text)
    {
        $text = $this->parser->replace($text, function () {
            return ' ';
        });

        return $text;
    }
}
