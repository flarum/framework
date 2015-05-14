<?php namespace Flarum\Mentions;

abstract class MentionsParserAbstract
{
    protected $pattern;

    public function match($string)
    {
        preg_match_all($this->pattern, $string, $matches);

        return $matches;
    }

    public function replace($string, $callback)
    {
        return preg_replace_callback($this->pattern, function ($matches) use ($callback) {
            return $callback($matches);
        }, $string);
    }
}
