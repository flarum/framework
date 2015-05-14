<?php namespace Flarum\Mentions;

class PostMentionsParser extends MentionsParserAbstract
{
    protected $pattern = '/\B@(?P<username>[a-z0-9_-]+)#(?P<number>\d+)/i';
}
