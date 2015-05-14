<?php namespace Flarum\Mentions;

class UserMentionsFormatter
{
    protected $parser;

    public function __construct(UserMentionsParser $parser)
    {
        $this->parser = $parser;
    }

    public function format($text, $post = null)
    {
        $text = $this->parser->replace($text, function ($match) {
            return '<a href="#/u/'.$match['username'].'" class="mention-user" data-user="'.$match['username'].'">'.$match['username'].'</a>';
        }, $text);

        return $text;
    }
}
