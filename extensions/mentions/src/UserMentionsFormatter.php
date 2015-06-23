<?php namespace Flarum\Mentions;

use Flarum\Core\Formatter\FormatterAbstract;
use Flarum\Core\Models\Post;

class UserMentionsFormatter extends FormatterAbstract
{
    protected $parser;

    public function __construct(UserMentionsParser $parser)
    {
        $this->parser = $parser;
    }

    public function afterPurification($text, Post $post = null)
    {
        $text = $this->ignoreTags($text, ['a', 'code', 'pre'], function ($text) {
            return $this->parser->replace($text, function ($match) {
                // TODO: use URL generator
                return '<a href="/u/'.$match['username'].'" class="mention-user" data-user="'.$match['username'].'">'.$match['username'].'</a>';
            }, $text);
        });

        return $text;
    }
}
