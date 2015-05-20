<?php namespace Flarum\Mentions;

class UserMentionsParser extends MentionsParserAbstract
{
    protected $pattern = '/\B@(?P<username>[a-z0-9_-]+)(?!#)/i';
}
